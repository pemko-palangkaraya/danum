<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {
    }

    /**
     * Display a listing of tenants.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        return response()->json([
            'data' => $this->tenantService->getAll(),
        ]);
    }

    /**
     * Store a newly created tenant.
     */
    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        $tenant = $this->tenantService->create(
            $request->validated(),
        );

        return response()->json([
            'data' => $tenant,
        ], 201);
    }

    /**
     * Display the specified tenant.
     */
    public function show(string $id): JsonResponse
    {
        $tenant = $this->tenantService->find($id);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        $this->authorize('view', $tenant);

        return response()->json([
            'data' => $tenant,
        ]);
    }

    /**
     * Update the specified tenant.
     */
    public function update(
        UpdateTenantRequest $request,
        string $id,
    ): JsonResponse {
        $tenant = $this->tenantService->find($id);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        $this->authorize('update', $tenant);

        $tenant = $this->tenantService->update(
            $tenant,
            $request->validated(),
        );

        return response()->json([
            'data' => $tenant,
        ]);
    }

    /**
     * Remove the specified tenant.
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = $this->tenantService->find($id);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        $this->authorize('delete', $tenant);

        $this->tenantService->delete($tenant);

        return response()->json([
            'message' => 'Tenant deleted successfully.',
        ]);
    }

    /**
     * Restore the specified tenant.
     */
    public function restore(string $id): JsonResponse
    {
        $tenant = $this->tenantService->findWithTrashed($id);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        $this->authorize('restore', $tenant);

        $this->tenantService->restore($tenant);

        return response()->json([
            'data' => $tenant->refresh(),
        ]);
    }
}
