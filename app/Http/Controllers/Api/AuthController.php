<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\User;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Enhanced login with device management and rate limiting
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'sometimes|string|max:255',
            'remember_me' => 'sometimes|boolean',
        ]);

        $email = $request->email;
        $key = 'login_attempts:' . $email;

        // Rate limiting: 5 attempts per minute
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60); // 1 minute lockout
            
            if ($user) {
                $user->incrementFailedAttempts();
            }
            
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // Check if account is locked
        if ($user->isAccountLocked()) {
            return response()->json([
                'success' => false,
                'message' => 'Account is temporarily locked due to multiple failed login attempts.',
                'retry_after' => $user->account_locked_until->diffInSeconds(now())
            ], 423);
        }

        // Check admin access for API
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Admin privileges required.'
            ], 403);
        }

        // Success - clear rate limiting and reset failed attempts
        RateLimiter::clear($key);
        $user->resetAccountLock();
        $user->updateLastActivity();

        // Device information
        $deviceName = $request->device_name ?? $this->getDefaultDeviceName($request);
        $expirationMinutes = $request->remember_me ? (60 * 24 * 30) : (60 * 24 * 7); // 30 days vs 7 days

        // Create token with enhanced abilities and device info
        $token = $user->createToken(
            $deviceName,
            ['admin:read', 'admin:write', 'user:manage'],
            now()->addMinutes($expirationMinutes)
        );

        // Store additional token metadata
        $tokenModel = $token->accessToken;
        $tokenModel->update([
            'name' => $deviceName,
            'last_used_at' => now(),
            'expires_at' => now()->addMinutes($expirationMinutes),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'last_activity' => $user->last_activity?->toISOString(),
                ],
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => now()->addMinutes($expirationMinutes)->toISOString(),
                'expires_in' => $expirationMinutes * 60, // seconds
                'device_name' => $deviceName,
                'abilities' => ['admin:read', 'admin:write', 'user:manage'],
            ]
        ]);
    }

    /**
     * Refresh token (extend expiration)
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        // Only allow refresh for actual PersonalAccessToken, not TransientToken
        if (!($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken)) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh not available for session-based authentication.'
            ], 400);
        }

        // Check if token is close to expiry (within 2 hours)
        if ($currentToken->expires_at && $currentToken->expires_at->diffInHours(now()) > 2) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh not needed yet.'
            ], 400);
        }

        // Extend token expiration
        $newExpiration = now()->addDays(7);
        $currentToken->update(['expires_at' => $newExpiration]);

        // Update user activity
        $user->updateLastActivity();

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'expires_at' => $newExpiration->toISOString(),
                'expires_in' => $newExpiration->diffInSeconds(now()),
            ]
        ]);
    }

    /**
     * Get all user's active tokens/devices
     */
    public function getDevices(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();
        $currentTokenId = $currentToken instanceof \Laravel\Sanctum\PersonalAccessToken ? $currentToken->id : null;
        
        $tokens = $user->tokens()
            ->where('expires_at', '>', now())
            ->orWhereNull('expires_at')
            ->get()
            ->map(function ($token) use ($currentTokenId) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'created_at' => $token->created_at->toISOString(),
                    'expires_at' => $token->expires_at?->toISOString(),
                    'is_current' => $token->id === $currentTokenId,
                    'abilities' => $token->abilities,
                ];
            });

        // Add current session info if using session-based auth
        if (!($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken)) {
            $tokens->prepend([
                'id' => 'session',
                'name' => 'Web Session (Current)',
                'last_used_at' => now()->toISOString(),
                'created_at' => 'N/A',
                'expires_at' => null,
                'is_current' => true,
                'abilities' => ['*'],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $tokens
        ]);
    }

    /**
     * Revoke specific device/token
     */
    public function revokeDevice(Request $request): JsonResponse
    {
        $request->validate([
            'token_id' => 'required|integer'
        ]);

        $user = $request->user();
        $token = $user->tokens()->find($request->token_id);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not found'
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device revoked successfully'
        ]);
    }

    /**
     * Enhanced logout with device info
     */
    public function logout(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();
        
        if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $tokenName = $currentToken->name;
            $currentToken->delete();
            $message = "Logged out from {$tokenName} successfully";
        } else {
            // For session-based authentication
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $message = "Logged out from web session successfully";
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Get enhanced user info with token details
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        // Prepare token info based on token type
        $tokenInfo = null;
        if ($currentToken instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $tokenInfo = [
                'device_name' => $currentToken->name,
                'expires_at' => $currentToken->expires_at?->toISOString(),
                'last_used_at' => $currentToken->last_used_at?->toISOString(),
                'abilities' => $currentToken->abilities,
                'type' => 'api_token'
            ];
        } else {
            $tokenInfo = [
                'device_name' => 'Web Session',
                'expires_at' => null,
                'last_used_at' => null,
                'abilities' => ['*'],
                'type' => 'session'
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'last_activity' => $user->last_activity?->toISOString(),
                'token_info' => $tokenInfo,
                'active_devices_count' => $user->tokens()->where('expires_at', '>', now())->orWhereNull('expires_at')->count(),
            ]
        ]);
    }

    /**
     * Revoke all tokens except current
     */
    public function revokeOtherDevices(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $request->user()->currentAccessToken()->id;
        
        $revokedCount = $user->tokens()
            ->where('id', '!=', $currentTokenId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "Revoked {$revokedCount} other devices successfully"
        ]);
    }

    /**
     * Revoke all tokens for the user
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $revokedCount = $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => "All {$revokedCount} tokens revoked successfully"
        ]);
    }

    /**
     * Get default device name from user agent
     */
    private function getDefaultDeviceName(Request $request): string
    {
        $userAgent = $request->userAgent();
        
        // Extract browser and OS info
        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        } else {
            $browser = 'Unknown Browser';
        }

        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iOS')) {
            $os = 'iOS';
        } else {
            $os = 'Unknown OS';
        }

        return "{$browser} on {$os}";
    }
} 