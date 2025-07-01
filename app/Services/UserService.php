<?php

namespace App\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Get all users with transformation
     */
    public function getAllUsers()
    {
        return UserResource::collection(User::latest()->get());
    }

    /**
     * Create a new user
     */
    public function createUser(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update a user
     */
    public function updateUser(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Delete a user
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Toggle user email verification
     */
    public function toggleVerification(User $user): array
    {
        if ($user->email_verified_at) {
            $user->update(['email_verified_at' => null]);
            $message = 'User email verification removed.';
        } else {
            $user->update(['email_verified_at' => now()]);
            $message = 'User email verified successfully.';
        }

        return [
            'user' => $user->fresh(),
            'message' => $message
        ];
    }

    /**
     * Reset user password to default
     */
    public function resetPassword(User $user): array
    {
        $tempPassword = 'password123';
        
        $user->update([
            'password' => Hash::make($tempPassword)
        ]);

        return [
            'user' => $user->fresh(),
            'message' => "User password reset to: {$tempPassword}",
            'temp_password' => $tempPassword
        ];
    }

    /**
     * Check if user can be deleted
     */
    public function canDeleteUser(User $user, ?User $currentUser = null): array
    {
        if ($currentUser && $user->id === $currentUser->id) {
            return [
                'can_delete' => false,
                'message' => 'You cannot delete your own account.'
            ];
        }

        // Add other business rules here (e.g., check for orders, etc.)
        
        return [
            'can_delete' => true,
            'message' => null
        ];
    }

    /**
     * Get user by ID with transformation
     */
    public function getUserById(int $id): ?User
    {
        return User::find($id);
    }
} 