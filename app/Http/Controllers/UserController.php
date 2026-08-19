<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    /**
     * Display a listing of users.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return response()->json([
            'data' => $this->userService->getAll(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->create(
            $request->validated(),
        );

        return response()->json([
            'data' => $user,
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if ($user === null) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $this->authorize('view', $user);

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(
        UpdateUserRequest $request,
        int $id,
    ): JsonResponse {
        $user = $this->userService->find($id);

        if ($user === null) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $this->authorize('update', $user);

        $user = $this->userService->update(
            $user,
            $request->validated(),
        );

        return response()->json([
            'data' => $user,
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->find($id);

        if ($user === null) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
