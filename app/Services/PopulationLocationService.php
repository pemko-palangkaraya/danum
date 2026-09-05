<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class PopulationLocationService
{
    public function optionsForTenant(
        string $tenantId,
        string $province = '',
        string $city = '',
        string $district = '',
    ): array {
        $tenant = $this->tenant($tenantId);

        if (! $tenant) {
            return $this->emptyOptions();
        }

        $province = $province !== '' ? $province : (string) ($tenant->province ?? '');
        $city = $city !== '' ? $city : (string) ($tenant->city ?? '');
        $district = $district !== '' ? $district : (string) ($tenant->district ?? '');

        return [
            'provinces' => collect([$tenant->province])->filter()->values(),
            'cities' => collect([$tenant->city])->filter()->values(),
            'districts' => $this->districtOptions($tenant, $province, $city),
            'villages' => $this->villageOptions($tenant, $province, $city, $district),
        ];
    }

    public function existsForTenant(
        string $tenantId,
        string $province,
        string $city,
        string $district,
        string $village,
    ): bool {
        $options = $this->optionsForTenant($tenantId, $province, $city, $district);

        return $options['districts']->contains($district)
            && $options['villages']->contains($village);
    }

    public function emptyOptions(): array
    {
        return [
            'provinces' => collect(),
            'cities' => collect(),
            'districts' => collect(),
            'villages' => collect(),
        ];
    }

    private function tenant(string $tenantId): ?Tenant
    {
        return Tenant::query()
            ->with(['category', 'parent.category', 'parent.parent.category'])
            ->whereKey($tenantId)
            ->where('status', TenantStatus::ACTIVE)
            ->first();
    }

    private function districtOptions(Tenant $tenant, string $province, string $city): Collection
    {
        if ($province !== (string) $tenant->province || $city !== (string) $tenant->city) {
            return collect();
        }

        $cityTenant = $this->cityTenant($tenant);

        if ($cityTenant) {
            $children = $this->childrenByCategory($cityTenant, ['kecamatan']);
            if ($children->isNotEmpty()) {
                return $children->pluck('district')->filter()->unique()->sort()->values();
            }
        }

        // Backward-compatible fallback for tenants created before the
        // explicit parent_tenant_id hierarchy existed.
        return $this->hierarchyTenantQuery(['kecamatan'])
            ->where('province', $province)
            ->where('city', $city)
            ->whereNotNull('district')
            ->where('district', '<>', '')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');
    }

    private function villageOptions(
        Tenant $tenant,
        string $province,
        string $city,
        string $district,
    ): Collection {
        if ($province !== (string) $tenant->province || $city !== (string) $tenant->city || $district === '') {
            return collect();
        }

        $cityTenant = $this->cityTenant($tenant);

        if ($cityTenant) {
            $districtTenant = $this->childrenByCategory($cityTenant, ['kecamatan'])
                ->firstWhere('district', $district);

            if ($districtTenant) {
                $children = $this->childrenByCategory($districtTenant, ['kelurahan', 'desa']);
                if ($children->isNotEmpty()) {
                    return $children->pluck('village')->filter()->unique()->sort()->values();
                }
            }
        }

        // Backward-compatible fallback for old/reference tenant rows.
        return $this->hierarchyTenantQuery(['kelurahan', 'desa'])
            ->where('province', $province)
            ->where('city', $city)
            ->where('district', $district)
            ->whereNotNull('village')
            ->where('village', '<>', '')
            ->distinct()
            ->orderBy('village')
            ->pluck('village');
    }

    private function cityTenant(Tenant $tenant): ?Tenant
    {
        if ($tenant->category?->code === 'pemerintah-kota') {
            return $tenant;
        }

        $parent = $tenant->parent;
        if ($parent?->category?->code === 'pemerintah-kota') {
            return $parent;
        }

        $grandParent = $parent?->parent;
        if ($grandParent?->category?->code === 'pemerintah-kota') {
            return $grandParent;
        }

        return Tenant::query()
            ->with('category')
            ->where('status', TenantStatus::ACTIVE)
            ->whereHas('category', fn ($query) => $query
                ->where('code', 'pemerintah-kota')
                ->where('is_active', true))
            ->where('province', $tenant->province)
            ->where('city', $tenant->city)
            ->orderBy('id')
            ->first();
    }

    private function childrenByCategory(Tenant $parent, array $categories): Collection
    {
        return Tenant::query()
            ->with('category')
            ->where('status', TenantStatus::ACTIVE)
            ->where('parent_tenant_id', $parent->id)
            ->whereHas('category', fn ($query) => $query
                ->whereIn('code', $categories)
                ->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'district', 'village', 'province', 'city']);
    }

    private function hierarchyTenantQuery(array $categories)
    {
        return Tenant::query()
            ->where('status', TenantStatus::ACTIVE)
            ->whereHas('category', fn ($query) => $query
                ->whereIn('code', $categories)
                ->where('is_active', true));
    }
}
