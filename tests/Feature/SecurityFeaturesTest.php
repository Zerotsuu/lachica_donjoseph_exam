<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_locks_after_five_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'role' => 'user'
        ]);

        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password'
            ]);
        }

        $user->refresh();
        
        $this->assertEquals(5, $user->failed_login_attempts);
        $this->assertNotNull($user->account_locked_until);
        $this->assertTrue($user->isAccountLocked());
    }

    public function test_locked_account_prevents_login_even_with_correct_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'role' => 'user'
        ]);

        // Lock the account manually
        $user->lockAccount();

        // Try to login with correct credentials
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password'
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_successful_login_resets_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct-password'),
            'role' => 'user'
        ]);

        // Make some failed attempts
        $user->update(['failed_login_attempts' => 3]);

        // Successful login
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'correct-password'
        ]);

        $user->refresh();
        
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->account_locked_until);
    }

    public function test_admin_user_can_access_dashboard()
    {
        $admin = User::factory()->create([
            'role' => 'admin'
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');
        
        $response->assertStatus(200);
    }

    public function test_guest_user_cannot_access_dashboard()
    {
        $user = User::factory()->create([
            'role' => 'user'
        ]);

        $response = $this->actingAs($user)->get('/dashboard');
        
        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Access denied. You do not have permission to access the admin dashboard.');
    }

    public function test_guest_user_redirected_to_home_after_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'user'
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertRedirect('/');
    }

    public function test_admin_user_redirected_to_dashboard_after_login()
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password'
        ]);

        $response->assertRedirect('/dashboard');
    }

    public function test_session_expiry_check()
    {
        $user = User::factory()->create();
        
        // Set last activity to 31 minutes ago
        $user->update([
            'last_activity' => now()->subMinutes(31)
        ]);

        $this->assertTrue($user->isSessionExpired());

        // Set last activity to 29 minutes ago
        $user->update([
            'last_activity' => now()->subMinutes(29)
        ]);

        $this->assertFalse($user->isSessionExpired());
    }

    public function test_account_lock_expires_after_five_minutes()
    {
        $user = User::factory()->create();
        
        // Set lock time to 6 minutes ago
        $user->update([
            'account_locked_until' => now()->subMinutes(6),
            'failed_login_attempts' => 5
        ]);

        $this->assertFalse($user->isAccountLocked());
    }
}
