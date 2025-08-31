<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JobSeekerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->role === 'job_seeker') {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized. Only job seekers can access this resource.'], 403);
    }
}
