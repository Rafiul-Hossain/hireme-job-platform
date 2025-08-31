<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class PaymentGatewayService
{
    /**
     * Initialize payment with Stripe
     */
    public function initializeStripePayment(Payment $payment)
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'bdt',
                        'unit_amount' => 10000, // 100 Taka in cents
                        'product_data' => [
                            'name' => 'Job Application Fee',
                            'description' => 'Application fee for job: ' . $payment->application->job->title,
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success', ['payment' => $payment->id]),
                'cancel_url' => route('payment.cancel', ['payment' => $payment->id]),
                'metadata' => [
                    'payment_id' => $payment->id,
                    'application_id' => $payment->application_id,
                ]
            ]);

            return [
                'success' => true,
                'session_id' => $session->id,
                'checkout_url' => $session->url,
            ];
        } catch (\Exception $e) {
            Log::error('Stripe payment initialization failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Initialize payment with SSLCommerz
     */
    public function initializeSSLCommerzPayment(Payment $payment)
    {
        try {
            $post_data = [
                'store_id' => config('services.sslcommerz.store_id'),
                'store_passwd' => config('services.sslcommerz.store_password'),
                'total_amount' => $payment->amount,
                'currency' => $payment->currency,
                'tran_id' => $payment->transaction_id,
                'success_url' => route('payment.success', ['payment' => $payment->id]),
                'fail_url' => route('payment.fail', ['payment' => $payment->id]),
                'cancel_url' => route('payment.cancel', ['payment' => $payment->id]),
                'cus_name' => $payment->user->name,
                'cus_email' => $payment->user->email,
                'product_name' => 'Job Application Fee',
                'product_category' => 'Service',
                'value_a' => $payment->id,
                'value_b' => $payment->application_id,
            ];

            $response = Http::post(config('services.sslcommerz.api_url') . '/gwprocess/v4/api.php', $post_data);

            if ($response->successful() && $response->json('status') === 'SUCCESS') {
                return [
                    'success' => true,
                    'checkout_url' => $response->json('GatewayPageURL'),
                ];
            }

            throw new Exception('SSLCommerz initialization failed: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('SSLCommerz payment initialization failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Process Stripe webhook
     */
    public function handleStripeWebhook($payload)
    {
        try {
            $event = \Stripe\Event::constructFrom($payload);

            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    $payment = Payment::find($session->metadata->payment_id);
                    
                    if ($payment) {
                        $payment->markAsCompleted([
                            'stripe_payment_id' => $session->payment_intent,
                            'stripe_session_id' => $session->id,
                        ]);
                    }
                    break;

                case 'payment_intent.payment_failed':
                    $session = $event->data->object;
                    $payment = Payment::find($session->metadata->payment_id);
                    
                    if ($payment) {
                        $payment->markAsFailed('Stripe payment failed');
                    }
                    break;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Stripe webhook handling failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Process SSLCommerz webhook
     */
    public function handleSSLCommerzWebhook($data)
    {
        try {
            $payment = Payment::where('transaction_id', $data['tran_id'])->first();

            if (!$payment) {
                throw new Exception('Payment not found');
            }

            if ($data['status'] === 'VALID' || $data['status'] === 'VALIDATED') {
                $payment->markAsCompleted([
                    'sslcommerz_tran_id' => $data['tran_id'],
                    'sslcommerz_val_id' => $data['val_id'],
                    'payment_method' => $data['card_type'],
                ]);
            } else {
                $payment->markAsFailed('SSLCommerz payment failed: ' . $data['status']);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('SSLCommerz webhook handling failed: ' . $e->getMessage());
            return false;
        }
    }
}
