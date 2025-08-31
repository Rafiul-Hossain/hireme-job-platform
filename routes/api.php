<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EmployerMiddleware;

// Test route for employer middleware
Route::get('/test-employer-middleware', function () {
    return response()->json(['message' => 'Employer middleware is working!']);
})->middleware(['jwt.verify', EmployerMiddleware::class]);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Middleware\AdminMiddleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test route for middleware
Route::get('/test-employer', function () {
    return response()->json(['message' => 'Employer middleware is working!']);
})->middleware(['jwt.verify', 'employer']);

// Public routes with rate limiting
Route::middleware('throttle:60,1')->group(function () {
    // Authentication
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Public job listings
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
});

// Protected routes (require authentication)
Route::middleware(['jwt.verify', 'throttle:60,1'])->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User job applications
    Route::post('/jobs/{job}/applications', [ApplicationController::class, 'store']);
    Route::get('/me/applications', [ApplicationController::class, 'myApplications']);
    Route::get('/applications/{application}', [ApplicationController::class, 'show']);

    // Job management (for employers)
    Route::apiResource('jobs', JobController::class)->except(['index', 'show']);

    // Employer routes
    Route::middleware(['jwt.verify', 'employer'])->prefix('employer')->group(function () {
        Route::get('/jobs', [JobController::class, 'companyJobs']);
        Route::put('/jobs/{job}/status', [JobController::class, 'updateJobStatus']);
        Route::get('/applications', [ApplicationController::class, 'companyApplications']);
        Route::put('/applications/{application}/status', [ApplicationController::class, 'updateStatus']);
    });

    // Admin routes
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        // User management
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::apiResource('users', UserController::class);

        // Job management
        Route::get('/jobs', [AdminController::class, 'getJobs']);
        Route::get('/jobs/{job}', [AdminController::class, 'getJob']);
        Route::delete('/jobs/{job}', [AdminController::class, 'deleteJob']);

        // Application management
        Route::get('/applications', [AdminController::class, 'getApplications']);
        Route::get('/applications/{application}', [AdminController::class, 'getApplication']);

        // Statistics
        Route::get('/statistics', [AdminController::class, 'getStatistics']);
        Route::get('/stats', [AdminController::class, 'getUserStats']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::post('/', [PaymentController::class, 'processPayment']);
        Route::get('/', [PaymentController::class, 'paymentHistory']);
        Route::get('/{id}', [PaymentController::class, 'getPayment']);
    });

    // Webhook routes (excluded from auth middleware)
    Route::post('/webhook/{gateway}', [PaymentController::class, 'handleWebhook'])
        ->withoutMiddleware(['jwt.verify'])
        ->where('gateway', 'stripe|sslcommerz');
});
