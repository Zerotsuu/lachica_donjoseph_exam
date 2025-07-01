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
     * This now creates a Sanctum token and redirects based on user role.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::guard('web')->user();

        // Create Sanctum token for the user with expiration
        $token = $user->createToken(
            'web-token', 
            ['web:access'], 
            now()->addDays(30) // 30 day expiration for web tokens
        )->plainTextToken;

        // Store token in session for frontend use
        session(['sanctum_token' => $token]);

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
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Get user from web guard (since they logged in via web)
        $user = Auth::guard('web')->user();
        
        if ($user) {
            // Revoke all Sanctum tokens for the user
            $user->tokens()->delete();
        }

        // Logout from web guard (clears session)
        Auth::guard('web')->logout();

        // Clear session token
        session()->forget('sanctum_token');
        
        // Clear and regenerate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
} 