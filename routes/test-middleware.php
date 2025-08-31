<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EmployerMiddleware;

Route::get('/test-employer-middleware', function () {
    return response()->json(['message' => 'Employer middleware is working!']);
})->middleware(['jwt.verify', EmployerMiddleware::class]);
