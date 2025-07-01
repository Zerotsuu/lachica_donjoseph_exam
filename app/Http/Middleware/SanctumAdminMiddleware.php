<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SanctumAdminMiddleware
{
    /**
     * Handle an incoming request
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated via Sanctum
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please authenticate first.',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        $user = Auth::guard('sanctum')->user();
        $token = $user->currentAccessToken();

        // Enhanced token validation
        if (!$this->validateToken($token, $request)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
                'error_code' => 'INVALID_TOKEN'
            ], 401);
        }

        // Check for admin role
        if (!$user->isAdmin()) {
            // Enhanced logging with more context
            $this->logUnauthorizedAccess($user, $request);

            // Rate limit unauthorized attempts per user
            $this->applyUnauthorizedRateLimit($user->id);

            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.',
                'error_code' => 'INSUFFICIENT_PRIVILEGES'
            ], 403);
        }

        // Check if account is locked or session expired
        if ($user->isAccountLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is temporarily locked.',
                'retry_after' => $user->account_locked_until->diffInSeconds(now()),
                'error_code' => 'ACCOUNT_LOCKED'
            ], 423);
        }

        if ($user->isSessionExpired()) {
            $token->delete();
            return response()->json([
                'success' => false,
                'message' => 'Session expired due to inactivity.',
                'error_code' => 'SESSION_EXPIRED'
            ], 401);
        }

        // Update token usage tracking
        $this->updateTokenUsage($token, $request);

        // Check for suspicious activity patterns
        if ($this->detectSuspiciousActivity($user, $request)) {
            $this->logSuspiciousActivity($user, $request);
        }

        return $next($request);
    }

    /**
     * Validate token integrity and expiration
     */
    private function validateToken($token, Request $request): bool
    {
        if (!$token) {
            return false;
        }

        // Only check expiration for actual PersonalAccessToken, not TransientToken
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            // Check token expiration
            if ($token->expires_at && $token->expires_at->isPast()) {
                $token->delete();
                return false;
            }

            // Update last used timestamp
            $token->update(['last_used_at' => now()]);
        }

        return true;
    }

    /**
     * Enhanced unauthorized access logging
     */
    private function logUnauthorizedAccess($user, Request $request): void
    {
        Log::warning('Unauthorized API access attempt', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'endpoint' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
            'headers' => [
                'authorization' => $request->header('authorization') ? 'Bearer [REDACTED]' : null,
                'x-forwarded-for' => $request->header('x-forwarded-for'),
                'x-real-ip' => $request->header('x-real-ip'),
            ]
        ]);
    }

    /**
     * Apply rate limiting for unauthorized access attempts
     */
    private function applyUnauthorizedRateLimit(int $userId): void
    {
        $key = "unauthorized_attempts:{$userId}";
        RateLimiter::hit($key, 300); // 5 minutes

        // Lock account if too many unauthorized attempts
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $user->lockAccount();
                Log::critical('Account locked due to repeated unauthorized access attempts', [
                    'user_id' => $userId,
                    'email' => $user->email,
                ]);
            }
        }
    }

    /**
     * Update token usage tracking
     */
    private function updateTokenUsage($token, Request $request): void
    {
        // Only track usage for actual PersonalAccessToken, not TransientToken
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            // Track API usage patterns
            $usageKey = "token_usage:{$token->id}:" . now()->format('Y-m-d-H');
            Cache::increment($usageKey, 1, 3600); // 1 hour TTL

            // Store request patterns for analysis
            $patterns = Cache::get("request_patterns:{$token->id}", []);
            $patterns[] = [
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
                'ip' => $request->ip(),
            ];

            // Keep only last 50 requests
            if (count($patterns) > 50) {
                $patterns = array_slice($patterns, -50);
            }

            Cache::put("request_patterns:{$token->id}", $patterns, 3600);
        }
    }

    /**
     * Detect suspicious activity patterns
     */
    private function detectSuspiciousActivity($user, Request $request): bool
    {
        $currentIp = $request->ip();
        $userAgent = $request->userAgent();
        
        // Check for IP address changes
        $lastKnownIp = Cache::get("user_last_ip:{$user->id}");
        if ($lastKnownIp && $lastKnownIp !== $currentIp) {
            Cache::put("user_last_ip:{$user->id}", $currentIp, 86400); // 24 hours
            return true;
        }

        // Check for user agent changes
        $lastKnownAgent = Cache::get("user_last_agent:{$user->id}");
        if ($lastKnownAgent && $lastKnownAgent !== $userAgent) {
            Cache::put("user_last_agent:{$user->id}", $userAgent, 86400);
            return true;
        }

        // First time tracking
        if (!$lastKnownIp) {
            Cache::put("user_last_ip:{$user->id}", $currentIp, 86400);
        }
        if (!$lastKnownAgent) {
            Cache::put("user_last_agent:{$user->id}", $userAgent, 86400);
        }

        // Check for rapid requests (potential bot behavior)
        $requestKey = "rapid_requests:{$user->id}";
        $requestCount = Cache::get($requestKey, 0);
        if ($requestCount > 100) { // More than 100 requests per minute
            return true;
        }
        Cache::increment($requestKey, 1, 60); // 1 minute window

        return false;
    }

    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity($user, Request $request): void
    {
        Log::info('Suspicious activity detected', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'endpoint' => $request->fullUrl(),
            'timestamp' => now()->toISOString(),
        ]);
    }
} 