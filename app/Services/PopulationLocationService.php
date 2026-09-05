<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Collection;

class PopulationLocationService
{
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
