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
            'provinces' => $this->provinceOptions($tenant, $province),
            'cities' => $this->cityOptions($tenant, $province, $city),
            'districts' => $this->districtOptions($tenant, $province, $city, $district),
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

    private function provinceOptions(Tenant $tenant, string $province): Collection
    {
        if ($tenant->category?->code === 'pemerintah-provinsi') {
            return collect([$tenant->province])->filter()->values();
        }

        return collect([$tenant->province])->filter()->values();
    }

    private function cityOptions(Tenant $tenant, string $province, string $city): Collection
    {
        if ($province !== (string) $tenant->province) {
            return collect();
        }

        if ($tenant->category?->code === 'pemerintah-provinsi') {
            return $this->childrenByCategory($tenant, ['pemerintah-kota', 'pemerintah-kabupaten'])
                ->pluck('city')
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        return collect([$tenant->city])->filter()->values();
    }

    private function districtOptions(
        Tenant $tenant,
        string $province,
        string $city,
        string $district,
    ): Collection {
        if ($province !== (string) $tenant->province || $city !== (string) $tenant->city) {
            return collect();
        }

        $categoryCode = $tenant->category?->code;

        if (in_array($categoryCode, ['pemerintah-kota', 'pemerintah-kabupaten'], true)) {
            return $this->childrenByCategory($tenant, ['kecamatan'])
                ->pluck('district')
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        if (in_array($categoryCode, ['kecamatan', 'kelurahan', 'desa'], true)) {
            return collect([$tenant->district])->filter()->values();
        }

        return collect();
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

        $categoryCode = $tenant->category?->code;

        if (in_array($categoryCode, ['pemerintah-kota', 'pemerintah-kabupaten'], true)) {
            $districtTenant = $this->childrenByCategory($tenant, ['kecamatan'])
                ->firstWhere('district', $district);

            return $districtTenant
                ? $this->childrenByCategory($districtTenant, ['kelurahan', 'desa'])
                    ->pluck('village')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                : collect();
        }

        if ($categoryCode === 'kecamatan') {
            return $this->childrenByCategory($tenant, ['kelurahan', 'desa'])
                ->pluck('village')
                ->filter()
                ->unique()
                ->sort()
                ->values();
        }

        if (in_array($categoryCode, ['kelurahan', 'desa'], true)) {
            return $district === (string) $tenant->district
                ? collect([$tenant->village])->filter()->values()
                : collect();
        }

        return collect();
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
}
