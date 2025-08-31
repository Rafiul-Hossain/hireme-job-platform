<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token',
                'code' => 'invalid_token'
            ], 401);
        });

        $this->renderable(function (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token has expired',
                'code' => 'token_expired'
            ], 401);
        });

        $this->renderable(function (JWTException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token not provided',
                'code' => 'token_not_provided'
            ], 401);
        });

        $this->renderable(function (AuthenticationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
                'code' => 'unauthenticated'
            ], 401);
        });

        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The given data was invalid',
                'errors' => $e->errors(),
                'code' => 'validation_failed'
            ], 422);
        });

        $this->renderable(function (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
                'code' => 'not_found'
            ], 404);
        });

        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'The requested URL was not found',
                'code' => 'url_not_found'
            ], 404);
        });

        $this->renderable(function (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'code' => 'server_error'
                ], 500);
            }
        });
    }
}
