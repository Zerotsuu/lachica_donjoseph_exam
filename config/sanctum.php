<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1,',
        Sanctum::currentApplicationUrlWithPort(),
        ','.Sanctum::currentRequestHost(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    'expiration' => env('SANCTUM_EXPIRATION_MINUTES', 10080), // 7 days default (more secure than 1 year)

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'pb_'), // Project-specific prefix

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Security Settings
    |--------------------------------------------------------------------------
    |
    | Enhanced security settings for token management and validation.
    |
    */

    'security' => [
        // Maximum tokens per user (prevent token hoarding)
        'max_tokens_per_user' => env('SANCTUM_MAX_TOKENS_PER_USER', 10),
        
        // Automatically prune expired tokens
        'prune_expired_tokens' => env('SANCTUM_PRUNE_EXPIRED', true),
        
        // Hash tokens in database (Laravel 11+ feature)
        'hash_tokens' => env('SANCTUM_HASH_TOKENS', true),
        
        // Token rotation policy
        'rotate_tokens_on_refresh' => env('SANCTUM_ROTATE_ON_REFRESH', false),
        
        // Enhanced token validation
        'validate_ip_address' => env('SANCTUM_VALIDATE_IP', false),
        'validate_user_agent' => env('SANCTUM_VALIDATE_USER_AGENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for different token operations.
    |
    */

    'rate_limiting' => [
        // Login attempts
        'login' => [
            'max_attempts' => env('SANCTUM_LOGIN_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('SANCTUM_LOGIN_DECAY_MINUTES', 1),
        ],
        
        // Token refresh
        'refresh' => [
            'max_attempts' => env('SANCTUM_REFRESH_MAX_ATTEMPTS', 10),
            'decay_minutes' => env('SANCTUM_REFRESH_DECAY_MINUTES', 1),
        ],
        
        // API requests per token
        'api_requests' => [
            'max_attempts' => env('SANCTUM_API_MAX_ATTEMPTS', 1000),
            'decay_minutes' => env('SANCTUM_API_DECAY_MINUTES', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Abilities
    |--------------------------------------------------------------------------
    |
    | Define available token abilities for fine-grained access control.
    |
    */

    'abilities' => [
        'admin:read' => 'Read admin resources',
        'admin:write' => 'Write admin resources',
        'admin:delete' => 'Delete admin resources',
        'user:read' => 'Read user resources',
        'user:write' => 'Write user resources',
        'user:manage' => 'Manage user accounts',
        'orders:read' => 'Read orders',
        'orders:write' => 'Write orders',
        'orders:cancel' => 'Cancel orders',
        'products:read' => 'Read products',
        'products:write' => 'Write products',
        'analytics:read' => 'Read analytics data',
    ],

];
