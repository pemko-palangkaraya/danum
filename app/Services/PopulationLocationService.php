<?php

declare(strict_types=1);

namespace App\Services;

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
        $category = $tenant->category?->code;

        return [
            'provinces' => collect([$tenant->province])->filter()->values(),
            'cities' => collect([$tenant->city])->filter()->values(),
            'districts' => $this->districtOptions($tenant, $province, $city, $category),
            'villages' => $this->villageOptions($tenant, $province, $city, $district, $category),
        ];
    }

    public function existsForTenant(
        string $tenantId,
        string $province,
        string $city,
        string $district,
        string $village,
    ): bool {
        $tenant = $this->tenant($tenantId);

        if (! $tenant) {
            return false;
        }

        return $this->optionsForTenant($tenantId, $province, $city, $district)['villages']->contains($village)
            && $this->optionsForTenant($tenantId, $province, $city, $district)['districts']->contains($district);
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
        return $this->values('district', [
            'province' => $province,
            'city' => $city,
        ]);
    }

    public function villages(string $province, string $city, string $district): Collection
    {
        return $this->values('village', [
            'province' => $province,
            'city' => $city,
            'district' => $district,
        ]);
    }

    public function exists(
        string $province,
        string $city,
        string $district,
        string $village,
    ): bool {
        return $this->baseQuery()
            ->where('province', $province)
            ->where('city', $city)
            ->where('district', $district)
            ->where('village', $village)
            ->exists();
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
            ->where('status', 'active')
            ->first();
    }

    private function districtOptions(Tenant $tenant, string $province, string $city, ?string $category): Collection
    {
        if ($province !== $tenant->province || $city !== $tenant->city) {
            return collect();
        }

        if ($category === 'pemerintah-kota') {
            return $this->values('district', [
                'province' => $tenant->province,
                'city' => $tenant->city,
            ]);
        }

        return collect([$tenant->district])->filter()->values();
    }

    private function villageOptions(
        Tenant $tenant,
        string $province,
        string $city,
        string $district,
        ?string $category,
    ): Collection {
        if ($province !== $tenant->province || $city !== $tenant->city || $district === '') {
            return collect();
        }

        if ($category === 'pemerintah-kota' || $category === 'kecamatan') {
            return $this->values('village', [
                'province' => $tenant->province,
                'city' => $tenant->city,
                'district' => $district,
            ]);
        }

        return $district === $tenant->district
            ? collect([$tenant->village])->filter()->values()
            : collect();
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
            ->where('status', 'active')
            ->whereNotNull('village')
            ->where('village', '<>', '')
            ->where('village', '<>', 'Pusat Pemerintahan')
            ->whereNotNull('district')
            ->where('district', '<>', '')
            ->where('district', '<>', 'Pusat Pemerintahan');
    }
}
