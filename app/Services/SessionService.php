<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class SessionService
{
    /**
     * Session keys that should be cleared on logout
     */
    private const LOGOUT_CLEAR_KEYS = [
        'sanctum_token',
        'cart',
        'user_preferences',
        'temp_data',
        'last_activity',
        'shopping_session',
    ];

    /**
     * Perform comprehensive session cleanup
     */
    public static function clearUserSession(Request $request, ?User $user = null, string $context = 'logout'): array
    {
        $sessionId = $request->session()->getId();
        
        // Log session cleanup initiation
        Log::info("Session cleanup initiated [{$context}]", [
            'user_id' => $user?->id,
            'session_id' => $sessionId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Clear specific session data
        foreach (self::LOGOUT_CLEAR_KEYS as $key) {
            session()->forget($key);
        }
        
        // Invalidate and regenerate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        $newSessionId = $request->session()->getId();
        
        // Log completion
        Log::info("Session cleanup completed [{$context}]", [
            'user_id' => $user?->id,
            'old_session_id' => $sessionId,
            'new_session_id' => $newSessionId,
            'keys_cleared' => self::LOGOUT_CLEAR_KEYS,
        ]);

        return [
            'old_session_id' => $sessionId,
            'new_session_id' => $newSessionId,
            'keys_cleared' => self::LOGOUT_CLEAR_KEYS,
        ];
    }

    /**
     * Revoke all Sanctum tokens for a user
     */
    public static function revokeAllTokens(User $user): int
    {
        $tokensCount = $user->tokens()->count();
        
        if ($tokensCount > 0) {
            $user->tokens()->delete();
            
            Log::info('All Sanctum tokens revoked', [
                'user_id' => $user->id,
                'tokens_revoked' => $tokensCount
            ]);
        }

        return $tokensCount;
    }

    /**
     * Revoke specific Sanctum token
     */
    public static function revokeToken(\Laravel\Sanctum\PersonalAccessToken $token): void
    {
        $tokenInfo = [
            'token_id' => $token->id,
            'token_name' => $token->name,
            'user_id' => $token->tokenable_id,
        ];
        
        $token->delete();
        
        Log::info('Sanctum token revoked', $tokenInfo);
    }

    /**
     * Log user logout activity
     */
    public static function logLogoutActivity(Request $request, User $user, string $type = 'web'): void
    {
        Log::info("User logout [{$type}]", [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get session cleanup keys (for configuration)
     */
    public static function getCleanupKeys(): array
    {
        return self::LOGOUT_CLEAR_KEYS;
    }

    /**
     * Add custom session keys to cleanup
     */
    public static function addCleanupKeys(array $keys): void
    {
        // This would require making LOGOUT_CLEAR_KEYS dynamic
        // Implementation depends on your specific needs
    }
} 