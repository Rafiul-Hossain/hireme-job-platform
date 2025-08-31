<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Application;
use App\Models\Job;
use App\Rules\ValidPaymentMethod;
use App\Exceptions\PaymentFailedException;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Process payment for a job application
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function processPayment(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'application_id' => ['required', 'exists:applications,id'],
            'payment_method' => ['required', 'string', new ValidPaymentMethod],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                $application = Application::with('job')->findOrFail($request->input('application_id'));
                
                // Check if user owns the application
                if ($application->user_id !== auth()->id()) {
                    return [
                        'success' => false,
                        'message' => 'Unauthorized',
                        'status' => 403
                    ];
                }

                // Check if payment already exists and is completed
                if ($application->isPaid()) {
                    return [
                        'success' => false,
                        'message' => 'Payment already processed for this application',
                        'payment' => $application->payment,
                        'status' => 400
                    ];
                }

                // Create invoice data
                $invoiceId = 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
                $transactionId = 'TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
                
                // Create payment record with invoice data
                $payment = Payment::create([
                    'user_id' => auth()->id(),
                    'application_id' => $application->id,
                    'amount' => 100.00, // 100 Taka
                    'currency' => 'BDT',
                    'payment_method' => $request->payment_method,
                    'status' => 'pending',
                    'transaction_id' => $transactionId,
                    'payment_details' => [
                        'invoice_id' => $invoiceId,
                        'method' => $request->payment_method,
                        'timestamp' => now()->toDateTimeString(),
                        'items' => [
                            [
                                'description' => 'Job Application Fee - ' . $application->job->title,
                                'quantity' => 1,
                                'unit_price' => 100.00,
                                'amount' => 100.00
                            ]
                        ]
                    ]
                ]);

                // Process payment through the payment gateway
                $result = $this->processPaymentGateway($payment);

                if (!$result['success']) {
                    throw new \Exception($result['error'] ?? 'Payment processing failed');
                }

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'payment' => $payment->load('application')
                ];
            });

            if (!$result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'payment' => $result['payment'] ?? null
                ], $result['status']);
            }

            return response()->json([
                'message' => $result['message'],
                'payment' => $result['payment']
            ], 201);

        } catch (\Exception $e) {
            Log::error('Payment processing error: ' . $e->getMessage(), [
                'application_id' => $request->input('application_id'),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to process payment. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Process payment through the appropriate gateway
     * 
     * @param Payment $payment
     * @return array
     */
    private $paymentGateway;

    public function __construct(PaymentGatewayService $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * Process payment through the appropriate gateway
     * 
     * @param Payment $payment
     * @return array
     */
    protected function processPaymentGateway(Payment $payment): array
    {
        try {
            // Validate payment amount
            if ($payment->amount != 100.00) {
                throw new PaymentFailedException('Invalid payment amount. Required amount is 100 Taka.');
            }

            // Initialize payment based on method
            if ($payment->payment_method === 'stripe') {
                $result = $this->paymentGateway->initializeStripePayment($payment);
            } else {
                $result = $this->paymentGateway->initializeSSLCommerzPayment($payment);
            }

            if (!$result['success']) {
                throw new PaymentFailedException($result['error'] ?? 'Payment initialization failed');
            }

            // Update payment status
            $payment->update([
                'status' => 'processing',
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'gateway_response' => $result,
                    'processed_at' => now()->toDateTimeString()
                ])
            ]);

            return [
                'success' => true,
                'checkout_url' => $result['checkout_url'] ?? null,
                'payment' => $payment
            ];

        } catch (\Exception $e) {
            Log::error('Payment gateway error: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'method' => $payment->payment_method,
                'trace' => $e->getTraceAsString()
            ]);

            $payment->update([
                'status' => 'failed',
                'payment_details' => array_merge($payment->payment_details ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toDateTimeString()
                ])
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get payment details
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function getPayment(string $id): JsonResponse
    {
        $payment = Payment::with(['application', 'application.job'])
            ->findOrFail($id);
            
        // Check if the authenticated user owns this payment
        if ($payment->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'payment' => $payment,
            'invoice' => $payment->invoice
        ]);
    }

    /**
     * Handle payment webhook
     * 
     * @param Request $request
     * @param string $gateway
     * @return JsonResponse
     */
    public function handleWebhook(Request $request, string $gateway): JsonResponse
    {
        try {
            $payload = $request->getContent();
            $signature = $request->header('X-Signature');
            
            // Verify webhook signature
            if (!$this->verifyWebhookSignature($gateway, $payload, $signature)) {
                Log::warning('Invalid webhook signature', [
                    'gateway' => $gateway,
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Process webhook based on gateway
            $method = 'handle' . ucfirst($gateway) . 'Webhook';
            if (method_exists($this, $method)) {
                return $this->$method($request);
            }

            return response()->json(['error' => 'Unsupported payment gateway'], 400);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage(), [
                'gateway' => $gateway,
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Stripe webhook
     */
    protected function handleStripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch(\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return DB::transaction(function () use ($event) {
            try {
                $paymentIntent = $event->data->object;
                
                if ($event->type === 'payment_intent.succeeded') {
                    $payment = Payment::where('transaction_id', $paymentIntent->id)->firstOrFail();
                    
                    if ($payment->status === 'completed') {
                        return response()->json(['status' => 'already_processed']);
                    }

                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                        'payment_details' => array_merge($payment->payment_details ?? [], [
                            'stripe_payment_intent' => $paymentIntent->id,
                            'payment_method' => $paymentIntent->payment_method_types[0] ?? null,
                            'receipt_url' => $paymentIntent->receipt_url ?? null,
                        ])
                    ]);

                    // Update application status
                    $payment->application->update(['status' => 'paid']);
                    
                    Log::info('Payment processed successfully', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->transaction_id
                    ]);
                }

                return response()->json(['status' => 'success']);

            } catch (\Exception $e) {
                Log::error('Error processing Stripe webhook: ' . $e->getMessage(), [
                    'event_id' => $event->id,
                    'event_type' => $event->type
                ]);
                
                // This will trigger a transaction rollback
                throw $e;
            }
        });
    }

    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(string $gateway, string $payload, ?string $signature): bool
    {
        if (empty($signature)) {
            return false;
        }

        switch ($gateway) {
            case 'stripe':
                $secret = config('services.stripe.webhook_secret');
                return !empty($secret); // In real app, verify with Stripe SDK
                
            case 'sslcommerz':
                $storePassword = config('services.sslcommerz.store_password');
                return !empty($storePassword); // In real app, verify with SSLCommerz SDK
                
            default:
                return false;
        }
    }

    /**
     * Get user's payment history
     * 
     * @return JsonResponse
     */
    public function paymentHistory(): JsonResponse
    {
        $perPage = request()->input('per_page', 10);
        
        $payments = Payment::where('user_id', auth()->id())
            ->with(['application.job'])
            ->latest()
            ->paginate($perPage);

        // Add invoice data to each payment
        $payments->getCollection()->transform(function ($payment) {
            $paymentData = $payment->toArray();
            $paymentData['invoice'] = $payment->invoice;
            return $paymentData;
        });

        return response()->json([
            'payments' => $payments,
            'total_paid' => $payments->sum('amount'),
            'status' => 'success'
        ]);
    }
}
