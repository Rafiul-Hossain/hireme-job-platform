<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    /**
     * Admin view of all jobs with advanced filtering
     */
    public function adminIndex(Request $request)
    {
        // Check if user is admin
        if (AuthFacade::user()?->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Job::with('company');

        // Core admin filters as per requirements
        // Filter by company
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->latest()->paginate($request->per_page ?? 15);
        return response()->json($jobs);
    }

    /**
     * Display a listing of jobs for regular users
     */
    public function index(Request $request)
    {
        $query = Job::with('company')
            ->where('status', 'active'); // Regular users only see active jobs

        // Basic filters for regular users
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Basic search for regular users
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%');
            });
        }

        $jobs = $query->latest()->paginate($request->per_page ?? 10);
        return response()->json($jobs);
    }

    /**
     * Store a newly created job
     */
    public function store(Request $request)
    {
        // Only allow employer users to create jobs
        if (AuthFacade::user()?->role !== 'employer') {
            return response()->json(['message' => 'Only employer accounts can create jobs'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'required|string',
            'salary' => 'required|numeric',
            'location' => 'required|string|max:255',
            'type' => 'required|in:full-time,part-time,contract,freelance',
            'category' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $job = Job::create([
            'company_id' => AuthFacade::id(),
            'title' => $request->title,
            'description' => $request->description,
            'requirements' => $request->requirements,
            'salary' => $request->salary,
            'location' => $request->location,
            'type' => $request->type,
            'category' => $request->category,
            'status' => 'active',
        ]);

        return response()->json($job, 201);
    }

    /**
     * Display the specified job
     */
    public function show($id)
    {
        $job = Job::with('company')->findOrFail($id);
        return response()->json($job);
    }

    /**
     * Update the specified job
     */
    public function update(Request $request, $id)
    {
        $user = AuthFacade::user();
        $job = Job::findOrFail($id);

        // Check if user has permission to update this job
        if ($user->role === 'admin' || ($user->role === 'employer' && $job->company_id === $user->id)) {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'requirements' => 'sometimes|string',
                'salary' => 'sometimes|numeric',
                'location' => 'sometimes|string|max:255',
                'type' => 'sometimes|in:full-time,part-time,contract,freelance',
                'category' => 'sometimes|string|max:255',
                'status' => $user->role === 'admin' ? 'sometimes|in:active,inactive,closed' : 'prohibited',
                'company_id' => 'prohibited', // Company ID cannot be changed
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $job->update($request->all());
            return response()->json($job);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Remove the specified job
     */
    public function destroy($id)
    {
        $user = AuthFacade::user();
        $job = Job::withTrashed()->findOrFail($id);

        // Only admin or the company that owns the job can delete it
        if ($user->role === 'admin' || ($user->role === 'employer' && $job->company_id === $user->id)) {
            // Store job data before deletion
            $deletedJob = $job->toArray();
            // Permanently delete the job and its related applications
            $job->forceDelete();
            return response()->json([
                'message' => 'Job permanently deleted successfully',
                'deleted_job' => $deletedJob
            ]);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Get jobs posted by the authenticated company (for employers)
     *
     * @queryParam status string Filter jobs by status (active, inactive, closed).
     * @queryParam search string Search jobs by title.
     * @queryParam type string Filter by job type (full-time, part-time, contract, freelance).
     * @queryParam per_page int Items per page (default: 15).
     */
    public function companyJobs(Request $request)
    {
        if (AuthFacade::user()?->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized. Only employers can view company jobs.'], 403);
        }

        $query = Job::where('company_id', AuthFacade::id())
            ->whereNull('deleted_at')
            ->withCount('applications')
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by job type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $jobs = $query->paginate($request->per_page ?? 15);

        return response()->json($jobs);
    }

    /**
     * Update job status (for employers)
     *
     * @urlParam id int required The ID of the job.
     * @bodyParam status string required The new status (active, inactive, closed).
     */
    public function updateJobStatus(Request $request, $id)
    {
        $job = Job::findOrFail($id);

        // Check if user is the employer who owns the job
        if (AuthFacade::user()?->role !== 'employer' || $job->company_id !== AuthFacade::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,closed'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $job->status = $request->status;
        $job->save();

        return response()->json([
            'message' => 'Job status updated successfully',
            'job' => $job
        ]);
    }
}
