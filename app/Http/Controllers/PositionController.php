<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\AssignPositionHolderRequest;
use App\Http\Requests\EndPositionHolderRequest;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Position;
use App\Services\PositionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function __construct(private readonly PositionService $positionService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);
        return response()->json(['data' => $this->positionService->getAll($request->user()->tenant_id)]);
    }

    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;
        return response()->json(['data' => $this->positionService->create($data)], 201);
    }

    public function show(Position $position): JsonResponse
    {
        $this->authorize('view', $position);
        return response()->json(['data' => $position]);
    }

    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $this->authorize('update', $position);
        return response()->json(['data' => $this->positionService->update($position, $request->validated())]);
    }

    public function destroy(Position $position): JsonResponse
    {
        $this->authorize('delete', $position);
        $this->positionService->delete($position);
        return response()->json(['message' => 'Position deleted successfully.']);
    }

    public function restore(string $id): JsonResponse
    {
        $position = $this->positionService->findWithTrashed($id);
        if ($position === null) return response()->json(['message' => 'Position not found.'], 404);
        $this->authorize('restore', $position);
        $this->positionService->restore($position);
        return response()->json(['data' => $this->positionService->findWithTrashed($id)]);
    }

    public function assignHolder(AssignPositionHolderRequest $request, Position $position): JsonResponse
    {
        $this->authorize('update', $position);
        $data = $request->validated();
        $holder = $this->positionService->assignHolder(
            $position,
            (int) $data['user_id'],
            Carbon::parse($data['started_at']),
            $data['assignment_status'],
        );
        return response()->json(['data' => $holder], 201);
    }

    public function endHolder(EndPositionHolderRequest $request, Position $position): JsonResponse
    {
        $this->authorize('update', $position);
        $activeHolder = $this->positionService->getActiveHolder($position);
        if ($activeHolder === null) return response()->json(['message' => 'Position has no active holder.'], 404);
        $holder = $this->positionService->endHolder($activeHolder, Carbon::parse($request->validated()['ended_at']));
        return response()->json(['data' => $holder]);
    }

    public function activeHolder(Position $position): JsonResponse
    {
        $this->authorize('view', $position);
        return response()->json(['data' => $this->positionService->getActiveHolder($position)]);
    }

    public function holderHistory(Position $position): JsonResponse
    {
        $this->authorize('view', $position);
        return response()->json(['data' => $this->positionService->getHolderHistory($position)]);
    }
}
