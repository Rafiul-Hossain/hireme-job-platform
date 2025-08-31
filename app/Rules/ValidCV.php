<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class ValidCV implements Rule
{
    private $errorMessage;
    private const MAX_SIZE = 5242880; // 5MB in bytes

    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            $this->errorMessage = 'Invalid file upload';
            return false;
        }

        // Check if file is valid
        if (!$value->isValid()) {
            $this->errorMessage = 'File upload failed';
            return false;
        }

        // Check extension
        $extension = strtolower($value->getClientOriginalExtension());
        if (!in_array($extension, ['pdf', 'doc', 'docx'])) {
            $this->errorMessage = 'Only PDF and Word documents are allowed';
            return false;
        }

        // Check MIME type
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        if (!in_array($value->getMimeType(), $allowedMimeTypes)) {
            $this->errorMessage = 'Invalid file type. Only PDF and Word documents are allowed';
            return false;
        }

        // Check size
        if ($value->getSize() > self::MAX_SIZE) {
            $this->errorMessage = 'File size must not exceed 5MB';
            return false;
        }

        return true;
    }

    public function message(): string
    {
        return $this->errorMessage ?? 'The CV file is invalid';
    }
}
