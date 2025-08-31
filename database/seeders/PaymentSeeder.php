<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    private $paymentMethods = ['bkash', 'nagad', 'rocket', 'credit_card', 'bank_transfer'];
    private $currencies = ['BDT', 'USD'];
    private $statuses = ['completed', 'failed', 'pending', 'refunded', 'cancelled'];

    public function run()
    {
        $applications = Application::with('job.company')->get();
        $admin = User::where('role', 'admin')->first();
        
        if ($applications->isEmpty() || !$admin) {
            return;
        }

        foreach ($applications as $application) {
            // Only create payments for some applications (about 70%)
            if (rand(1, 10) <= 7) {
                $status = $this->statuses[array_rand($this->statuses)];
                $isCompleted = $status === 'completed';
                $paymentMethod = $this->paymentMethods[array_rand($this->paymentMethods)];
                $currency = $paymentMethod === 'credit_card' ? 'USD' : 'BDT';
                $amount = $this->generateAmount($currency);
                $paidAt = $isCompleted ? now()->subDays(rand(1, 30)) : null;
                
                Payment::create([
                    'transaction_id' => 'TXN' . strtoupper(Str::random(10)) . time(),
                    'user_id' => $application->user_id,
                    'application_id' => $application->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_method' => $paymentMethod,
                    'payment_details' => $this->generatePaymentDetails($paymentMethod, $application->user, $application->job->company),
                    'receipt_url' => $isCompleted ? 'https://example.com/receipts/' . Str::random(20) : null,
                    'status' => $status,
                    'paid_at' => $paidAt,
                ]);
            }
        }
    }

    private function generateAmount($currency)
    {
        if ($currency === 'USD') {
            return rand(5, 50); // $5 to $50 for international payments
        }
        
        // For BDT, typical job application fees
        $amounts = [
            100, 200, 300, 500, 750, 1000, 1500, 2000
        ];
        
        return $amounts[array_rand($amounts)];
    }

    private function generatePaymentDetails($method, $user, $company)
    {
        $details = [
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'recipient' => $company->company_name,
            'purpose' => 'Job Application Fee',
            'method_specific' => []
        ];

        switch ($method) {
            case 'bkash':
            case 'nagad':
            case 'rocket':
                $details['method_specific'] = [
                    'phone' => '01' . rand(5, 9) . rand(1000000, 9999999),
                    'transaction_id' => strtoupper(substr($method, 0, 1)) . rand(1000000, 9999999),
                    'reference' => 'JOB' . rand(1000, 9999)
                ];
                break;
                
            case 'credit_card':
                $details['method_specific'] = [
                    'card_last4' => '**** **** **** ' . rand(1000, 9999),
                    'card_brand' => ['visa', 'mastercard', 'amex'][rand(0, 2)],
                    'receipt_url' => 'https://example.com/receipts/' . Str::random(20)
                ];
                break;
                
            case 'bank_transfer':
                $details['method_specific'] = [
                    'bank_name' => ['DBBL', 'BRAC Bank', 'City Bank', 'Eastern Bank'][rand(0, 3)],
                    'account_number' => '****' . rand(1000, 9999),
                    'reference' => 'JOB' . rand(1000, 9999)
                ];
                break;
        }

        return $details;
    }
}
