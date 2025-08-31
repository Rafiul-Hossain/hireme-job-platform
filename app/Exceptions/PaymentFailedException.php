<?php

namespace App\Exceptions;

use Exception;

class PaymentFailedException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Payment processing failed',
            'details' => $this->getMessage(),
            'code' => 'payment_failed'
        ], 400);
    }
}
