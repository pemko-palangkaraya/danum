<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\PositionHolder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class LetterVariableSourceResolver
{
    public function __construct(
        private readonly LetterVariableDateService $date,
        private readonly PopulationReferenceService $references,
    ) {}

    public function system(?PositionHolder $holder = null): array
    {
        $tenant = $holder?->user?->tenant ?? auth()->user()?->tenant;
        if (! $tenant) return [];

        $name = (string) ($holder?->user?->name ?? $tenant->head_name ?? '');
        $title = (string) ($holder?->position?->name ?? $tenant->head_title ?? '');

        return [
            'tenant_name' => $tenant->name,
            'tenant_city' => $tenant->city,
            'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village,
            'tenant_province' => $tenant->province,
            'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone,
            'tenant_email' => $tenant->email,
            'tenant_head_name' => $name,
            'tenant_head_title' => $title,
            'nama_ttd' => $name,
            'jabatan_ttd' => $title,
        ];
    }

    public function citizen(Citizen $citizen): array
    {
        $gender = $this->references->label('gender', $citizen->jenis_kelamin, (string) $citizen->jenis_kelamin);
        $bloodType = $this->references->label('blood_type', $citizen->golongan_darah, (string) $citizen->golongan_darah);
        $maritalStatus = $this->references->label('marital_status', $citizen->status_perkawinan, (string) $citizen->status_perkawinan);
        $religion = $this->references->label('religion', $citizen->agama, (string) $citizen->agama);
        $citizenship = $this->references->label('citizenship', $citizen->kewarganegaraan, (string) $citizen->kewarganegaraan);
        $populationStatus = $this->references->label('population_status', $citizen->status_kependudukan, (string) $citizen->status_kependudukan);

        return [
            'citizen_nik' => $citizen->nik,
            'citizen_nama_lengkap' => $citizen->nama_lengkap,
            'citizen_tempat_lahir' => $citizen->tempat_lahir,
            'citizen_tanggal_lahir' => $this->date->format($citizen->tanggal_lahir),
            'citizen_jenis_kelamin' => $gender,
            'citizen_golongan_darah' => $bloodType,
            'citizen_agama' => $religion,
            'citizen_status_perkawinan' => $maritalStatus,
            'citizen_pendidikan' => $citizen->pendidikan,
            'citizen_pekerjaan' => $citizen->pekerjaan,
            'citizen_kewarganegaraan' => $citizenship,
            'citizen_no_passport' => $citizen->no_passport,
            'citizen_no_kitap' => $citizen->no_kitap,
            'citizen_nama_ayah' => $citizen->nama_ayah,
            'citizen_nik_ayah' => $citizen->nik_ayah,
            'citizen_nama_ibu' => $citizen->nama_ibu,
            'citizen_nik_ibu' => $citizen->nik_ibu,
            'citizen_status_kependudukan' => $populationStatus,
            'recipient_name' => $citizen->nama_lengkap,
            'recipient_nik' => $citizen->nik,
            'recipient_gender' => $gender,
            'recipient_birth_place' => $citizen->tempat_lahir,
            'recipient_birth_date' => $this->date->format($citizen->tanggal_lahir),
            'recipient_religion' => $religion,
            'recipient_occupation' => $citizen->pekerjaan,
        ];
    }

    public function family(Citizen $citizen): array
    {
        $family = $this->familyFor($citizen);
        if (! $family) {
            return [
                'values' => ['recipient_address' => '', 'nama_pasangan' => '-'],
                'children' => [['nomor' => '1', 'nama' => '-']],
                'members' => [],
            ];
        }

        $members = $family->activeMembers
            ->filter(fn (FamilyMember $member) => (string) $member->citizen_id !== (string) $citizen->id)
            ->values();

        $subjectRelation = $this->relationType(
            $family->activeMembers->first(fn (FamilyMember $member) => (string) $member->citizen_id === (string) $citizen->id)?->hubungan_dalam_keluarga
        );

        $spouse = $this->spouseFor($family, $citizen, $members, $subjectRelation);
        $childrenMembers = $this->childrenFor($members, $subjectRelation);
        $children = $childrenMembers
            ->map(fn (FamilyMember $member, int $index) => $this->familyMemberRow($member, $index + 1, 'Anak'))
            ->all();

        $familyMembers = collect();
        if ($spouse?->citizen) {
            $familyMembers->push($this->familyMemberRow($spouse, 1, 'Pasangan'));
        }
        foreach ($childrenMembers as $member) {
            $familyMembers->push($this->familyMemberRow($member, $familyMembers->count() + 1, 'Anak'));
        }

        return [
            'values' => [
                'recipient_address' => $this->address($family),
                'nama_pasangan' => (string) ($spouse?->citizen?->nama_lengkap ?: '-'),
            ],
            'children' => $children ?: [['nomor' => '1', 'nama' => '-']],
            'members' => $familyMembers->all(),
        ];
    }

    public function age(Citizen $citizen, mixed $deathDate): string
    {
        $normalized = $this->date->normalize($deathDate);
        if (! $citizen->tanggal_lahir || ! $normalized) return '';

        try {
            $age = Carbon::parse($citizen->tanggal_lahir)->diffInYears(Carbon::parse($normalized), false);
            return $age >= 0 ? (string) floor((float) $age) : '';
        } catch (\Throwable) {
            return '';
        }
    }

    private function familyFor(Citizen $citizen): ?Family
    {
        $membership = $citizen->activeFamilyMembership()->with('family.activeMembers.citizen')->first();
        if ($membership?->family) return $membership->family;

        return $citizen->headedFamilies()->where('status', 'active')->with('activeMembers.citizen')->first();
    }

    /** @param Collection<int, FamilyMember> $members */
    private function spouseFor(Family $family, Citizen $citizen, Collection $members, ?string $subjectRelation): ?FamilyMember
    {
        if ($subjectRelation === 'child') return null;

        if ($subjectRelation === 'spouse') {
            $head = $family->headCitizen;
            if (! $head || (string) $head->id === (string) $citizen->id) return null;

            return $members->first(fn (FamilyMember $member) => (string) $member->citizen_id === (string) $head->id)
                ?? tap(new FamilyMember, fn (FamilyMember $member) => $member->setRelation('citizen', $head));
        }

        return $members->first(fn (FamilyMember $member) => $this->relationType($member->hubungan_dalam_keluarga) === 'spouse');
    }

    /** @param Collection<int, FamilyMember> $members */
    private function childrenFor(Collection $members, ?string $subjectRelation): Collection
    {
        return in_array($subjectRelation, [null, 'head', 'spouse'], true)
            ? $members->filter(fn (FamilyMember $member) => $this->relationType($member->hubungan_dalam_keluarga) === 'child')->values()
            : collect();
    }

    private function familyMemberRow(FamilyMember $member, int $number, string $relation): array
    {
        $citizen = $member->citizen;
        $gender = $this->references->label('gender', $citizen?->jenis_kelamin, (string) ($citizen?->jenis_kelamin ?? ''));

        return [
            'nomor' => (string) $number,
            'nama' => (string) ($citizen?->nama_lengkap ?: '-'),
            'gender' => $gender,
            'tpt_lahir' => (string) ($citizen?->tempat_lahir ?: '-'),
            'tanggal_lahir' => $this->date->format($citizen?->tanggal_lahir),
            'ket' => $relation,
        ];
    }

    private function relationType(mixed $relation): ?string
    {
        return match (mb_strtolower(trim((string) $relation))) {
            'head', 'kepala', 'kepala keluarga', 'kepala_keluarga' => 'head',
            'spouse', 'suami', 'istri', 'pasangan' => 'spouse',
            'child', 'anak' => 'child',
            default => null,
        };
    }

    private function address(Family $family): string
    {
        return collect([
            $family->alamat,
            filled($family->rt) ? 'RT ' . $family->rt : null,
            filled($family->rw) ? 'RW ' . $family->rw : null,
            $family->kelurahan,
            $family->kecamatan,
            $family->kabupaten_kota,
            $family->provinsi,
            $family->kode_pos,
        ])->filter(fn ($value) => filled($value))->implode(', ');
    }
}
