<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class SanctumAuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     * Using session-based authentication as recommended for SPAs.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::guard('web')->user();

        // For SPAs, we only need session-based authentication (no tokens)
        $request->session()->regenerate();

        // Reset failed attempts on successful login
        $user->resetAccountLock();
        $user->updateLastActivity();

        // Redirect based on user role
        if ($user->isAdmin()) {
            return redirect()->intended(route('dashboard', absolute: false));
        } else {
            // Redirect Guest/User role to the shop (landing page)
            return redirect()->intended(route('home', absolute: false));
        }
    }

    /**
     * Destroy an authenticated session using standard session-based logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        
        if ($user) {
            // Log the logout activity
            \Log::info('User logout initiated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Standard Laravel session-based logout
        Auth::guard('web')->logout();

        // Invalidate session and regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        \Log::info('Session invalidated and token regenerated');

        // Set logout success message and redirect
        session()->flash('success', 'You have been successfully logged out.');
        
        return redirect('/');
    }
} 