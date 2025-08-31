<?php

namespace App\Services;

class PaymentService
{
    /**
     * Process payment for job application
     * 
     * @param string $method Payment method (stripe, mock, etc.)
     * @param float $amount Amount to be paid
     * @param array $paymentData Additional payment data
     * @return array
     */
    public function processPayment(string $method, float $amount = 100.00, array $paymentData = []): array
    {
        // In a real implementation, this would integrate with payment gateways
        // For now, we'll simulate a successful payment
        
        $reference = 'MOCK' . time() . rand(1000, 9999);
        
        // Log the payment for testing
        \Illuminate\Support\Facades\Log::info('Payment processed', [
            'method' => $method,
            'amount' => $amount,
            'reference' => $reference,
            'data' => $paymentData
        ]);
        
        return [
            'success' => true,
            'reference' => $reference,
            'amount' => $amount,
            'verified_at' => now(),
            'method' => $method
        ];
    }
    
    /**
     * Verify a payment
     * 
     * @param string $reference Payment reference
     * @param float $expectedAmount Expected amount
     * @return bool
     */
    public function verifyPayment(string $reference, float $expectedAmount = 100.00): bool
    {
        // In a real implementation, this would verify with the payment gateway
        // For mock purposes, we'll just check if it looks like a valid reference
        return str_starts_with($reference, 'MOCK') && $expectedAmount === 100.00;
    }
}
