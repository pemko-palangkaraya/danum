<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

class TenantProfileService
{
    /**
     * Fields that belong to the tenant profile.
     *
     * System-controlled fields such as id, code, and status
     * are intentionally excluded.
     */
    private const PROFILE_FIELDS = [
        'name',
        'province',
        'city',
        'district',
        'village',
        'address',
        'phone',
        'email',
        'logo',
        'head_name',
        'head_title',
    ];

    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {}

    public function show(Tenant $tenant): Tenant
    {
        return $tenant;
    }

    public function update(Tenant $tenant, array $data): Tenant
    {
        $profileData = array_intersect_key(
            $data,
            array_flip(self::PROFILE_FIELDS),
        );

        return $this->tenantRepository->update($tenant, $profileData);
    }
}
