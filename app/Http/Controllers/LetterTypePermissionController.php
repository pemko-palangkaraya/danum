<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Services\AuditLogService;
use App\Services\LetterTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LetterTypePermissionController extends Controller
{
    public function __construct(
        private readonly LetterTypeService $service,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request, string $id): JsonResponse
    {
        $letterType = $this->letterType($id);
        $this->authorize('view', $letterType);

        return response()->json(['data' => $letterType->permissions()->with('tenant')->get()]);
    }

    public function store(Request $request, string $id): JsonResponse
    {
        $letterType = $this->letterType($id);
        $this->authorize('update', $letterType);
        $data = $request->validate(['tenant_id' => ['required', 'uuid', 'exists:tenants,id']]);

        $existing = $letterType->permissions()->where('tenant_id', $data['tenant_id'])->first();
        $permission = $this->service->grantTenantPermission($letterType, $data['tenant_id']);

        if (! $existing || ! $existing->allowed) {
            $this->auditLog->record(
                'letter_type.permission.granted',
                auth()->user(),
                $permission,
                $existing?->only(['tenant_id', 'letter_type_id', 'allowed']),
                $permission->only(['tenant_id', 'letter_type_id', 'allowed']),
                $data['tenant_id'],
            );
        }

        return response()->json(['data' => $permission->load('tenant')], 201);
    }

    public function destroy(Request $request, string $id, string $tenantId): JsonResponse
    {
        $letterType = $this->letterType($id);
        $this->authorize('update', $letterType);

        if (! Tenant::query()->whereKey($tenantId)->exists()) {
            return response()->json(['message' => 'Tenant not found.'], 404);
        }

        $permission = $letterType->permissions()->where('tenant_id', $tenantId)->first();
        $removed = $this->service->revokeTenantPermission($letterType, $tenantId);

        if (! $removed) {
            return response()->json(['message' => 'Permission not found.'], 404);
        }

        if ($permission?->allowed) {
            $permission->refresh();
            $this->auditLog->record(
                'letter_type.permission.revoked',
                auth()->user(),
                $permission,
                ['tenant_id' => $tenantId, 'letter_type_id' => $letterType->id, 'allowed' => true],
                $permission->only(['tenant_id', 'letter_type_id', 'allowed']),
                $tenantId,
            );
        }

        return response()->json(['message' => 'Permission revoked successfully.']);
    }

    private function letterType(string $id): LetterType
    {
        $letterType = $this->service->find($id, null);

        if ($letterType === null) {
            abort(404, 'Letter type not found.');
        }

        return $letterType;
    }
}
