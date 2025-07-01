<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $users = $this->userService->getAllUsers();
        return Inertia::render('Users', ['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $this->userService->createUser($request->getProcessedData());
        return redirect()->back()->with('success', 'User created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        return Inertia::render('Users/Show', [
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->userService->updateUser($user, $request->getProcessedData());
        return redirect()->back()->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $validation = $this->userService->canDeleteUser($user, auth()->user());
        
        if (!$validation['can_delete']) {
            return redirect()->back()->with('error', $validation['message']);
        }

        $this->userService->deleteUser($user);
        return redirect()->back()->with('success', 'User deleted successfully!');
    }

    /**
     * Toggle user verification status.
     */
    public function toggleVerification(User $user): RedirectResponse
    {
        $result = $this->userService->toggleVerification($user);
        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Reset user password.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        $result = $this->userService->resetPassword($user);
        return redirect()->back()->with('success', $result['message']);
    }
}
