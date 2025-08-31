<?php

namespace App\Exceptions;

use Exception;

class InvalidFileException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid file upload',
            'details' => $this->getMessage(),
            'code' => 'invalid_file'
        ], 422);
    }
}
