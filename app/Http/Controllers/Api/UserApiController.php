<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserApiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(): JsonResponse
    {
        $users = $this->userService->getAllUsers();
        return $this->successResponse($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->getProcessedData());
        return $this->successResponse($user, 'User created successfully!', 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return $this->successResponse(new UserResource($user));
    }

    /**
     * Update the specified user.
     */
    public function update(UserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->updateUser($user, $request->getProcessedData());
        return $this->successResponse($updatedUser, 'User updated successfully!');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $validation = $this->userService->canDeleteUser($user, auth('sanctum')->user());
        
        if (!$validation['can_delete']) {
            return $this->errorResponse($validation['message'], 400);
        }

        $this->userService->deleteUser($user);
        return $this->successResponse(null, 'User deleted successfully!');
    }

    /**
     * Toggle user verification status.
     */
    public function toggleVerification(User $user): JsonResponse
    {
        $result = $this->userService->toggleVerification($user);
        return $this->successResponse($result['user'], $result['message']);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(User $user): JsonResponse
    {
        $result = $this->userService->resetPassword($user);
        return $this->successResponse($result['user'], $result['message']);
    }
} 