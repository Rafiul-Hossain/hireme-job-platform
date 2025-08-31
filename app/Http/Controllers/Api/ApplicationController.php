<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Http\Requests\StoreApplicationRequest;
use App\Exceptions\InvalidFileException;
use App\Rules\ValidCV;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{
    /**
     * Display a listing of the applications
     */
    public function index(Request $request)
    {
        // Regular users can only see their own applications with basic filtering
        $query = Application::with(['job.company'])
            ->where('user_id', Auth::id());

        // Basic filters for regular users
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        $applications = $query->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json($applications);
    }

    /**
     * Store a newly created application
     */
    public function store(StoreApplicationRequest $request, Job $job)
    {
        // Check if job is active and not expired
        if ($job->status !== 'active' || ($job->deadline && now()->gt($job->deadline))) {
            return response()->json(['message' => 'This job is no longer accepting applications.'], 400);
        }

        // Check if user has already applied
        if (Application::where('user_id', Auth::id())->where('job_id', $job->id)->exists()) {
            return response()->json(['message' => 'You have already applied to this job.'], 400);
        }

        // Start database transaction
        \DB::beginTransaction();
        
        try {
            // Store the CV file
            $cvPath = $request->file('cv')->store('cvs', 'public');

            // Process payment (required)
            $paymentService = app(\App\Services\PaymentService::class);
            $paymentResult = $paymentService->processPayment(
                $request->payment_method,
                100.00, // Fixed 100 TK fee
                [
                    'job_id' => $job->id,
                    'user_id' => Auth::id(),
                    'reference' => $request->payment_reference
                ]
            );

            if (!$paymentResult['success']) {
                throw new \Exception('Payment verification failed');
            }

            // Create application
            $application = Application::create([
                'user_id' => Auth::id(),
                'job_id' => $job->id,
                'cover_letter' => $request->cover_letter,
                'cv_path' => $cvPath,
                'status' => 'pending',
                'payment_reference' => $request->payment_reference,
                'payment_amount' => 100.00,
                'payment_method' => $request->payment_method,
                'payment_status' => 'completed',
                'payment_verified_at' => now()
            ]);

            // Create payment record without receipt_url since it's not needed for mock payments
            $payment = new \App\Models\Payment([
                'transaction_id' => $request->payment_reference,
                'user_id' => Auth::id(),
                'application_id' => $application->id,
                'amount' => 100.00,
                'currency' => 'BDT',
                'payment_method' => $request->payment_method,
                'status' => 'completed',
                'paid_at' => now(),
                'payment_details' => json_encode([
                    'reference' => $request->payment_reference,
                    'job_id' => $job->id,
                    'amount' => 100.00,
                    'currency' => 'BDT',
                    'method' => $request->payment_method
                ])
            ]);
            
            // Save payment record
            $payment->save();
            
            // Update application with payment reference and amount
            $application->update([
                'payment_reference' => $request->payment_reference,
                'payment_amount' => 100.00,
                'payment_method' => $request->payment_method,
                'payment_status' => 'completed',
                'payment_verified_at' => now()
            ]);

            // Commit transaction
            \DB::commit();

            return response()->json([
                'message' => 'Application submitted successfully with payment.',
                'application' => $application->load(['job', 'payment'])
            ], 201);

        } catch (\Exception $e) {
            // Rollback transaction on error
            \DB::rollBack();
            
            // If anything goes wrong, delete the uploaded file if it exists
            if (isset($cvPath) && Storage::disk('public')->exists($cvPath)) {
                Storage::disk('public')->delete($cvPath);
            }

            Log::error('Application submission failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to submit application. ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Display the specified application
     */
    public function show($id)
    {
        $application = Application::with(['job.company', 'user'])
            ->findOrFail($id);

        // Check if user is authorized to view this application
        if ($application->user_id !== Auth::id() &&
            $application->job->company_id !== Auth::id() &&
            Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($application);
    }

    /**
     * Update application status
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $application = Application::with('job')->findOrFail($id);

        // Check if user is authorized to update this application
        if (!in_array($user->role, ['admin', 'employer'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // For employer, check if they own the job
        if ($user->role === 'employer' && $application->job->company_id != $user->id) {
            return response()->json(['message' => 'You can only update applications for your company\'s jobs'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending_payment,pending,shortlisted,rejected,hired',
            'feedback' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Only allow status changes if the application is not already accepted
        if ($application->status === 'accepted' && $request->status !== 'accepted') {
            return response()->json([
                'message' => 'Cannot change status of an accepted application',
            ], 400);
        }

        $application->update([
            'status' => $request->status,
            'feedback' => $request->feedback ?? $application->feedback,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        // Notify the applicant about status change (you can implement notification logic here)
        // $application->user->notify(new ApplicationStatusUpdated($application));

        return response()->json([
            'message' => 'Application status updated successfully',
            'application' => $application->load('job', 'user')
        ]);
    }

    /**
     * Get all applications for the authenticated employer's jobs
     */
    public function companyApplications(Request $request)
    {
        $user = Auth::user();

        // Check if user is an employer
        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized. Only employers can view company applications.'], 403);
        }

        $query = Application::with(['job', 'user'])
            ->whereHas('job', function($q) use ($user) {
                $q->where('company_id', $user->id);
            });

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        // Search by applicant name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applications = $query->latest()
                            ->paginate($request->per_page ?? 15);

        return response()->json($applications);
    }

    /**
     * Get job seeker's application history
     *
     * @queryParam status string Filter by application status (pending, accepted, rejected).
     * @queryParam job_id integer Filter by job ID.
     * @queryParam date_from string Filter by application date from (YYYY-MM-DD).
     * @queryParam date_to string Filter by application date to (YYYY-MM-DD).
     * @queryParam per_page integer Items per page (default: 15).
     */
    public function myApplications(Request $request)
    {
        // Ensure user is a job seeker
        if (Auth::user()?->role !== 'job_seeker') {
            return response()->json(['message' => 'Unauthorized. Only job seekers can access this resource.'], 403);
        }

        $query = Application::with(['job.company'])
            ->where('user_id', Auth::id())
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by job
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $applications = $query->paginate($request->per_page ?? 15);

        return response()->json($applications);
    }

    /**
     * Admin view of all applications with advanced filtering
     *
     * @queryParam status string Filter by application status (pending, accepted, rejected).
     * @queryParam company_id integer Filter by company ID.
     * @queryParam job_id integer Filter by job ID.
     * @queryParam user_id integer Filter by user ID.
     * @queryParam date_from string Filter by application date from (YYYY-MM-DD).
     * @queryParam date_to string Filter by application date to (YYYY-MM-DD).
     * @queryParam search string Search by user name, email, or job title.
     * @queryParam per_page integer Items per page (default: 15).
     */
    public function adminIndex(Request $request)
    {
        // Check if user is admin
        if (Auth::user()?->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $query = Application::with(['user', 'job.company']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by company
        if ($request->has('company_id')) {
            $query->whereHas('job', function($q) use ($request) {
                $q->where('company_id', $request->company_id);
            });
        }

        // Filter by job
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('job', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            });
        }

        // Get paginated results
        $applications = $query->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($applications);
    }
}
