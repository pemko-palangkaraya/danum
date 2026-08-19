<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutgoingLetterRequest;
use App\Http\Requests\UpdateOutgoingLetterRequest;
use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OutgoingLetterController extends Controller
{
    public function __construct(
        private readonly OutgoingLetterService $outgoingLetterService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OutgoingLetter::class);

        return response()->json([
            'data' => $this->outgoingLetterService->getAll($request->user()->tenant_id),
        ]);
    }

    public function store(StoreOutgoingLetterRequest $request): JsonResponse
    {
        $this->authorize('create', OutgoingLetter::class);

        $outgoingLetter = $this->outgoingLetterService->create([
            ...$request->validated(),
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return response()->json(['data' => $outgoingLetter], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('view', $outgoingLetter);

        return response()->json(['data' => $outgoingLetter]);
    }

    public function update(UpdateOutgoingLetterRequest $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('update', $outgoingLetter);

        return response()->json([
            'data' => $this->outgoingLetterService->update(
                $outgoingLetter,
                $request->validated(),
            ),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->findForTenant($id, $request);

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('delete', $outgoingLetter);
        $this->outgoingLetterService->delete($outgoingLetter);

        return response()->json([
            'message' => 'Outgoing letter deleted successfully.',
        ]);
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        $outgoingLetter = $this->outgoingLetterService->findWithTrashed(
            $id,
            $request->user()->tenant_id,
        );

        if ($outgoingLetter === null) {
            return $this->notFoundResponse();
        }

        $this->authorize('restore', $outgoingLetter);
        $this->outgoingLetterService->restore($outgoingLetter);

        return response()->json(['data' => $outgoingLetter->refresh()]);
    }

    private function findForTenant(string $id, Request $request): ?OutgoingLetter
    {
        return $this->outgoingLetterService->find($id, $request->user()->tenant_id);
    }

    private function notFoundResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Outgoing letter not found.',
        ], 404);
    }
}
