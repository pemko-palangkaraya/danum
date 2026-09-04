<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FamilyService
{
    public function tenants()
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name']);
    }

    public function findForTenant(string $tenantId, string $id): Family
    {
        return $this->query($tenantId)->findOrFail($id);
    }

    public function findDetail(string $tenantId, string $id): ?Family
    {
        return $this->query($tenantId)
            ->with(['headCitizen', 'members.citizen'])
            ->find($id);
    }

    public function paginate(string $tenantId, string $search, int $perPage)
    {
        return $this->query($tenantId)
            ->with('headCitizen')
            ->withCount([
                'activeMembers as active_members_count',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('no_kk', 'like', '%'.$search.'%')
                        ->orWhere('alamat', 'like', '%'.$search.'%')
                        ->orWhere('kelurahan', 'like', '%'.$search.'%')
                        ->orWhere('kecamatan', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findHeadCandidates(string $tenantId, string $search)
    {
        return $tenantId === '' ? collect() : $this->citizenCandidates($tenantId, $search);
    }

    public function findMemberCandidates(string $tenantId, string $search)
    {
        return $tenantId === '' ? collect() : $this->citizenCandidates($tenantId, $search);
    }

    public function findCitizen(string $tenantId, string $citizenId): Citizen
    {
        return Citizen::query()
            ->whereKey($citizenId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
    }

    public function selectedHead(string $tenantId, string $citizenId): ?Citizen
    {
        if ($tenantId === '' || $citizenId === '') {
            return null;
        }

        return Citizen::query()
            ->whereKey($citizenId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function save(string $tenantId, array $input, ?string $editingId, int|string $userId): Family
    {
        $input['head_citizen_id'] = $input['head_citizen_id'] ?: null;

        $data = Validator::make($input, $this->rules($tenantId, $editingId))->validate();

        if (! empty($data['head_citizen_id'])) {
            $this->findCitizen($tenantId, $data['head_citizen_id']);
        }

        $data['tenant_id'] = $tenantId;
        $data['updated_by'] = $userId;

        if ($editingId !== null) {
            $family = $this->findForTenant($tenantId, $editingId);
            $family->update($data);
            return $family->refresh();
        }

        $data['created_by'] = $userId;

        return Family::create($data);
    }

    public function addMember(
        string $tenantId,
        string $familyId,
        string $citizenId,
        string $relationship,
        string $status = 'active'
    ): void {
        $family = $this->findForTenant($tenantId, $familyId);
        $citizen = $this->findCitizen($tenantId, $citizenId);

        $data = Validator::make(
            [
                'hubungan_dalam_keluarga' => $relationship,
                'status' => $status,
            ],
            [
                'hubungan_dalam_keluarga' => ['required', 'string', 'max:40'],
                'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            ]
        )->validate();

        FamilyMember::updateOrCreate(
            ['family_id' => $family->id, 'citizen_id' => $citizen->id],
            [
                'hubungan_dalam_keluarga' => $data['hubungan_dalam_keluarga'],
                'status' => $data['status'],
            ]
        );
    }

    public function removeMember(string $tenantId, string $familyId, string $citizenId): void
    {
        $family = $this->findForTenant($tenantId, $familyId);

        FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('citizen_id', $citizenId)
            ->delete();
    }

    private function query(string $tenantId)
    {
        return Family::query()->where('tenant_id', $tenantId);
    }

    private function citizenCandidates(string $tenantId, string $search)
    {
        return Citizen::query()
            ->where('tenant_id', $tenantId)
            ->when($search !== '', fn ($q) => $q->where(function ($query) use ($search): void {
                $query->whereRaw('LOWER(nik) LIKE LOWER(?)', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(nama_lengkap) LIKE LOWER(?)', ['%'.$search.'%']);
            }))
            ->orderBy('nama_lengkap')
            ->limit(10)
            ->get(['id', 'nik', 'nama_lengkap']);
    }

    private function rules(string $tenantId, ?string $editingId): array
    {
        return [
            'no_kk' => [
                'required', 'string', 'size:16',
                Rule::unique('families', 'no_kk')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($editingId),
            ],
            'head_citizen_id' => [
                'nullable', 'uuid',
                Rule::exists('citizens', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'alamat' => ['required', 'string', 'max:255'],
            'rt' => ['required', 'string', 'max:10'],
            'rw' => ['required', 'string', 'max:10'],
            'kelurahan' => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'kabupaten_kota' => ['required', 'string', 'max:100'],
            'provinsi' => ['required', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'string', 'max:30'],
        ];
    }
}
