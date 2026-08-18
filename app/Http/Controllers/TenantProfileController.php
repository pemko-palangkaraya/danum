<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTenantProfileRequest;
use App\Services\TenantProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantProfileController extends Controller
{
    public function __construct(
        private readonly TenantProfileService $tenantProfileService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $this->authorize('viewProfile', $tenant);

        return response()->json([
            'data' => $this->tenantProfileService->show($tenant),
        ]);
    }

    public function update(UpdateTenantProfileRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $this->authorize('updateProfile', $tenant);

        $tenant = $this->tenantProfileService->update(
            $tenant,
            $request->validated(),
        );

        return response()->json([
            'data' => $tenant,
        ]);
    }
}
