<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterTypeRequest;
use App\Http\Requests\UpdateLetterTypeRequest;
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
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', LetterType::class);

        return response()->json([
            'data' => $this->letterTypeService->getAll($request->user()->tenant_id),
        ]);
    }

    /**
     * Store a newly created letter type.
     */
    public function store(StoreLetterTypeRequest $request): JsonResponse
    {
        $this->authorize('create', LetterType::class);

        $letterType = $this->letterTypeService->create(
            [
                ...$request->validated(),
                'tenant_id' => $request->user()->tenant_id,
            ],
        );

        return response()->json([
            'data' => $letterType,
        ], 201);
    }

    /**
     * Display the specified letter type.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $letterType = $this->letterTypeService->find($id, $request->user()->tenant_id);

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
        UpdateLetterTypeRequest $request,
        string $id,
    ): JsonResponse {
        $letterType = $this->letterTypeService->find($id, $request->user()->tenant_id);

        if ($letterType === null) {
            return response()->json([
                'message' => 'Letter type not found.',
            ], 404);
        }

        $this->authorize('update', $letterType);

        $letterType = $this->letterTypeService->update(
            $letterType,
            $request->validated(),
        );

        return response()->json([
            'data' => $letterType,
        ]);
    }

    /**
     * Remove the specified letter type.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $letterType = $this->letterTypeService->find($id, $request->user()->tenant_id);

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
    public function restore(Request $request, string $id): JsonResponse
    {
        $letterType = $this->letterTypeService->findWithTrashed($id, $request->user()->tenant_id);

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
