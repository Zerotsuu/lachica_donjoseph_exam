<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has admin role
        if (!$user->isAdmin()) {
            // Log the unauthorized access attempt
            \Log::warning('Unauthorized CMS access attempt', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'ip' => $request->ip(),
                'route' => $request->route()->getName()
            ]);

            // If it's an API request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Admin privileges required.',
                    'redirect' => route('home')
                ], 403);
            }

            // For web requests, redirect to home with error message
            return redirect()->route('home')->with('error', 'Access denied. You do not have permission to access the admin dashboard.');
        }

        return $next($request);
    }
} 