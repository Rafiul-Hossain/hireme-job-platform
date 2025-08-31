<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users (admin only)
     *
     * @queryParam role string Filter by user role (job_seeker, employee, admin).
     * @queryParam company string Filter by company name (for employees).
     * @queryParam search string Search by name or email.
     * @queryParam date_from string Filter by registration date from (YYYY-MM-DD).
     * @queryParam date_to string Filter by registration date to (YYYY-MM-DD).
     * @queryParam per_page integer Items per page (default: 15).
     * @queryParam with_trashed boolean Include soft-deleted users (admin only).
     */
    public function index(Request $request)
    {
        // Check if user is admin
        if (AuthFacade::user()?->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        $query = User::query();

        // Include trashed users if requested
        if ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by company name (for employees)
        if ($request->has('company')) {
            $query->where('company_name', 'like', '%' . $request->company . '%');
        }

        // Filter by registration date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get paginated results with counts
        $users = $query->withCount(['jobs', 'applications'])
                      ->latest()
                      ->paginate($request->per_page ?? 15);

        return response()->json($users);
    }

    /**
     * Store a newly created user (admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:job_seeker,admin,employer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified user (admin only)
     */
    public function show($id)
    {
        // Only allow admin to view user details
        if (AuthFacade::user()?->role !== 'admin') {
            return response()->json(['message' => 'Only admin can view user details'], 403);
        }

        $user = User::withCount(['jobs', 'applications'])
                   ->withTrashed()
                   ->findOrFail($id);

        return response()->json($user);
    }

    /**
     * Update the specified user (admin only)
     */
    public function update(Request $request, $id)
    {
        // Only allow admin to update users
        if (AuthFacade::user()?->role !== 'admin') {
            return response()->json(['message' => 'Only admin can update users'], 403);
        }

        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|in:job_seeker,admin,employer',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'status' => 'sometimes|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $updateData = $request->except('password');

        // Only update password if provided
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json($user);
    }

    /**
     * Remove the specified user (admin only, soft delete)
     */
    public function destroy($id)
    {
        // Only allow admin to delete users
        if (AuthFacade::user()?->role !== 'admin') {
            return response()->json(['message' => 'Only admin can delete users'], 403);
        }

        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === AuthFacade::id()) {
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Get platform statistics (admin only)
     */
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_jobs' => \App\Models\Job::count(),
            'total_applications' => \App\Models\Application::count(),
            'total_payments' => \App\Models\Payment::count(),
            'revenue' => \App\Models\Payment::sum('amount'),
        ]);
    }
}
