<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FamilyMember> */
class FamilyMemberFactory extends Factory
{
    protected $model = FamilyMember::class;

    public function definition(): array
    {
        return [
            'family_id' => Family::factory(),
            'citizen_id' => Citizen::factory(),
            'hubungan_dalam_keluarga' => 'child',
            'urutan' => $this->faker->numberBetween(1, 10),
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => null,
            'status' => 'active',
        ];
    }
}
