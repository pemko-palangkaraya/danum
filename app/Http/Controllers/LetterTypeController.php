<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LetterType;
use App\Services\LetterTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LetterTypeController extends Controller
{
    public function __construct(
        private readonly LetterTypeService $letterTypeService,
    ) {}

    /**
     * Display a listing of letter types.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', LetterType::class);

        return response()->json([
            'data' => $this->letterTypeService->getAll(),
        ]);
    }

    /**
     * Store a newly created letter type.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', LetterType::class);

        $letterType = $this->letterTypeService->create(
            $request->all(),
        );

        return response()->json([
            'data' => $letterType,
        ], 201);
    }

    /**
     * Display the specified letter type.
     */
    public function show(string $id): JsonResponse
    {
        $letterType = $this->letterTypeService->find($id);

        if ($letterType === null) {
            return response()->json([
                'message' => 'Letter type not found.',
            ], 404);
        }

        $this->authorize('view', $letterType);

        return response()->json([
            'data' => $letterType,
        ]);
    }

    /**
     * Update the specified letter type.
     */
    public function update(
        Request $request,
        string $id,
    ): JsonResponse {
        $letterType = $this->letterTypeService->find($id);

        if ($letterType === null) {
            return response()->json([
                'message' => 'Letter type not found.',
            ], 404);
        }

        $this->authorize('update', $letterType);

        $letterType = $this->letterTypeService->update(
            $letterType,
            $request->all(),
        );

        return response()->json([
            'data' => $letterType,
        ]);
    }

    /**
     * Remove the specified letter type.
     */
    public function destroy(string $id): JsonResponse
    {
        $letterType = $this->letterTypeService->find($id);

        if ($letterType === null) {
            return response()->json([
                'message' => 'Letter type not found.',
            ], 404);
        }

        $this->authorize('delete', $letterType);

        $this->letterTypeService->delete($letterType);

        return response()->json([
            'message' => 'Letter type deleted successfully.',
        ]);
    }

    /**
     * Restore the specified letter type.
     */
    public function restore(string $id): JsonResponse
    {
        $letterType = $this->letterTypeService->findWithTrashed($id);

        if ($letterType === null) {
            return response()->json([
                'message' => 'Letter type not found.',
            ], 404);
        }

        $this->authorize('restore', $letterType);

        $this->letterTypeService->restore($letterType);

        return response()->json([
            'data' => $letterType->refresh(),
        ]);
    }
}
