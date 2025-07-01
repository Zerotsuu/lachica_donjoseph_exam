<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'failed_login_attempts',
        'account_locked_until',
        'last_activity',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => self::ROLE_USER,
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'account_locked_until' => 'datetime',
        'last_activity' => 'datetime',
    ];

    /**
     * Check if the user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if the account is currently locked
     */
    public function isAccountLocked(): bool
    {
        if (!$this->account_locked_until) {
            return false;
        }

        if (now()->greaterThan($this->account_locked_until)) {
            // Lock has expired, reset the lock
            $this->resetAccountLock();
            return false;
        }

        return true;
    }

    /**
     * Increment failed login attempts and lock account if necessary
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');
        $this->refresh(); // Refresh to get the updated value

        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount();
        }
    }

    /**
     * Lock the account for 5 minutes
     */
    public function lockAccount(): void
    {
        $this->update([
            'account_locked_until' => now()->addMinutes(5)
        ]);
    }

    /**
     * Reset failed login attempts and unlock account
     */
    public function resetAccountLock(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'account_locked_until' => null
        ]);
    }

    /**
     * Update last activity timestamp
     */
    public function updateLastActivity(): void
    {
        $this->update(['last_activity' => now()]);
    }

    /**
     * Check if user session has expired (30 minutes of inactivity)
     */
    public function isSessionExpired(): bool
    {
        if (!$this->last_activity) {
            return false;
        }

        return $this->last_activity->lt(now()->subMinutes(30));
    }

    /**
     * Get the user's cart.
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    /**
     * Get or create the user's cart.
     */
    public function getOrCreateCart(): Cart
    {
        return $this->cart()->firstOrCreate(['user_id' => $this->id]);
    }

    /**
     * Get the user's orders.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
