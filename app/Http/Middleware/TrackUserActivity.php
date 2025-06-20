<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Check if session has expired (30 minutes of inactivity)
            if ($user->isSessionExpired()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // If it's an API request, return JSON response
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Session expired due to inactivity. Please log in again.',
                        'redirect' => route('login')
                    ], 401);
                }
                
                // For web requests, redirect to login with message
                return redirect()->route('login')->with('status', 'Session expired due to inactivity. Please log in again.');
            }
            
            // Update last activity
            $user->updateLastActivity();
        }

        return $next($request);
    }
}
