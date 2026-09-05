<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PopulationReferenceService
{
    public function all(): array
    {
        return [
            'gender' => $this->group('gender'),
            'blood_type' => $this->group('blood_type'),
            'marital_status' => $this->group('marital_status'),
            'religion' => $this->group('religion'),
            'citizenship' => $this->group('citizenship'),
            'family_relationship' => $this->group('family_relationship'),
        ];
    }

    public function group(string $group): Collection
    {
        return DB::table('population_reference_data')
            ->where('group', $group)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['code', 'label']);
    }
}
