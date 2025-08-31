<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobController;
use App\Http\Middleware\EmployerMiddleware;

Route::get('/test-employer', function () {
    return response()->json(['message' => 'Employer middleware is working!']);
})->middleware(['jwt.verify', EmployerMiddleware::class]);

// This is a temporary test file to verify middleware functionality
