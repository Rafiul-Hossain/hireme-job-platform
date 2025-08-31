<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Models\Job;
use App\Models\Application;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cover_letter' => 'required|string|min:50|max:2000',
            'cv' => [
                'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:5120', // 5MB
            ],
            'payment_method' => 'required|string|in:mock',
            'payment_reference' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!str_starts_with(strtoupper($value), 'MOCK')) {
                        return $fail('Payment reference must start with MOCK');
                    }
                    
                    $paymentService = app(\App\Services\PaymentService::class);
                    if (!$paymentService->verifyPayment($value, 100.00)) {
                        return $fail('Invalid payment reference.');
                    }
                },
                'max:255',
            ],
        ];
    }

    /**
     * Custom error messages for validation
     */
    public function messages(): array
    {
        return [
            'cover_letter.required' => 'A cover letter is required',
            'cover_letter.min' => 'The cover letter must be at least :min characters',
            'cover_letter.max' => 'The cover letter may not be greater than :max characters',
            'cv.required' => 'A CV file is required',
            'cv.mimes' => 'The CV must be a file of type: pdf, doc, docx',
            'cv.max' => 'The CV must not be greater than 5MB',
            'payment_method.in' => 'The selected payment method is invalid.',
        ];
    }
}
