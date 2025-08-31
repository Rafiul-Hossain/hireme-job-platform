<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Job;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Get all users with optional filters
     */
    public function getUsers(Request $request)
    {
        $query = User::query();

        // Filter by role if provided
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by company if provided
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $users = $query->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Get all jobs with optional filters
     */
    public function getJobs(Request $request)
    {
        $query = Job::with('company');

        // Filter by company if provided
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Add more filters as needed
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $jobs
        ]);
    }

    /**
     * Get a specific job by ID
     */
    public function getJob($id)
    {
        $job = Job::with(['company', 'applications'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job
        ]);
    }

    /**
     * Delete a job (admin only)
     */
    public function deleteJob($id)
    {
        $job = Job::withTrashed()->findOrFail($id);

        // Store job data before deletion
        $deletedJob = $job->toArray();

        // Permanently delete the job
        $job->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Job permanently deleted successfully',
            'deleted_job' => $deletedJob
        ]);
    }

    /**
     * Get all applications with optional filters
     */
    public function getApplications(Request $request)
    {
        $query = Application::with(['job', 'user']);

        // Filter by job if provided
        if ($request->has('job_id')) {
            $query->where('job_id', $request->job_id);
        }

        // Filter by user if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->get();

        return response()->json([
            'success' => true,
            'data' => $applications
        ]);
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        $stats = [
            'total_users' => User::count(),
            'total_companies' => User::where('role', 'company')->count(),
            'total_job_seekers' => User::where('role', 'job_seeker')->count(),
            'total_jobs' => Job::count(),
            'total_applications' => Application::count(),
            'active_jobs' => Job::where('status', 'active')->count(),
            'pending_applications' => Application::where('status', 'pending')->count(),
            'hired_applications' => Application::where('status', 'hired')->count(),
            'rejected_applications' => Application::where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get single application details
     */
    public function getApplication($id)
    {
        $application = Application::with(['job', 'user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $application
        ]);
    }

    /**
     * Get user statistics
     */
    public function getUserStats()
    {
        $stats = [
            'users_by_role' => User::select('role', DB::raw('count(*) as count'))
                                 ->groupBy('role')
                                 ->pluck('count', 'role'),
            'users_by_status' => User::select('status', DB::raw('count(*) as count'))
                                   ->groupBy('status')
                                   ->pluck('count', 'status'),
            'new_users_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'active_users' => User::where('last_active_at', '>=', now()->subDays(30))->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
