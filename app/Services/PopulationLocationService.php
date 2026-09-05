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
            'districts' => $this->districtOptions($province, $city),
            'villages' => $this->villageOptions($province, $city, $district),
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

    public function provinces(): Collection
    {
        return $this->baseQuery()
            ->whereNotNull('province')
            ->where('province', '<>', '')
            ->distinct()
            ->orderBy('province')
            ->pluck('province');
    }

    public function cities(string $province): Collection
    {
        return $this->values('city', ['province' => $province]);
    }

    public function districts(string $province, string $city): Collection
    {
        return $this->districtOptions($province, $city);
    }

    public function villages(string $province, string $city, string $district): Collection
    {
        return $this->villageOptions($province, $city, $district);
    }

    public function exists(
        string $province,
        string $city,
        string $district,
        string $village,
    ): bool {
        return $this->districtOptions($province, $city)->contains($district)
            && $this->villageOptions($province, $city, $district)->contains($village);
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
            ->with('category')
            ->whereKey($tenantId)
            ->where('status', TenantStatus::ACTIVE)
            ->first();
    }

    private function districtOptions(string $province, string $city): Collection
    {
        if ($province === '' || $city === '') {
            return collect();
        }

        return $this->hierarchyTenantQuery(['kecamatan'])
            ->where('province', $province)
            ->where('city', $city)
            ->whereNotNull('district')
            ->where('district', '<>', '')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');
    }

    private function villageOptions(string $province, string $city, string $district): Collection
    {
        if ($province === '' || $city === '' || $district === '') {
            return collect();
        }

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

    private function hierarchyTenantQuery(array $categories)
    {
        return Tenant::query()
            ->where('status', TenantStatus::ACTIVE)
            ->whereHas('category', fn ($query) => $query
                ->whereIn('code', $categories)
                ->where('is_active', true));
    }

    private function values(string $column, array $filters): Collection
    {
        return $this->baseQuery()
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->where($filters)
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }

    private function baseQuery()
    {
        return Tenant::query()
            ->where('status', TenantStatus::ACTIVE)
            ->whereNotNull('village')
            ->where('village', '<>', '')
            ->where('village', '<>', 'Pusat Pemerintahan')
            ->whereNotNull('district')
            ->where('district', '<>', '')
            ->where('district', '<>', 'Pusat Pemerintahan');
    }
}
