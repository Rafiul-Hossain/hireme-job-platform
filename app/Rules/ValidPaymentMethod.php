<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPaymentMethod implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array(strtolower($value), $this->validMethods())) {
            $fail('The selected payment method is not valid.');
        }
    }

    /**
     * Get the list of valid payment methods.
     *
     * @return array
     */
    protected function validMethods(): array
    {
        return [
            'stripe',
            'sslcommerz',
            // Add more payment methods as needed
        ];
    }
}
