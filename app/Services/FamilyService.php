<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            ->with(['headCitizen', 'activeMembers.citizen'])
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
        return $tenantId === '' ? collect() : $this->memberCandidates($tenantId, $search);
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

        if (! app(PopulationLocationService::class)->existsForTenant(
            $tenantId,
            $data['provinsi'],
            $data['kabupaten_kota'],
            $data['kecamatan'],
            $data['kelurahan'],
        )) {
            throw ValidationException::withMessages([
                'kelurahan' => 'Wilayah harus berada dalam cakupan tenant yang sedang digunakan.',
            ]);
        }

        if (! empty($data['head_citizen_id'])) {
            $this->findCitizen($tenantId, $data['head_citizen_id']);
        }

        $data['tenant_id'] = $tenantId;
        $data['updated_by'] = $userId;

        return DB::transaction(function () use ($tenantId, $data, $editingId, $userId): Family {
            if ($editingId !== null) {
                $family = $this->findForTenant($tenantId, $editingId);
                $oldValues = $this->familyAuditValues($family);
                $family->update($data);
                $family = $family->refresh();

                $this->auditLogService()->record(
                    action: 'population.family.updated',
                    user: $this->actor($userId),
                    auditable: $family,
                    oldValues: $oldValues,
                    newValues: $this->familyAuditValues($family),
                    tenantId: $tenantId,
                );

                return $family;
            }

            $data['created_by'] = $userId;
            $family = Family::create($data);

            $this->auditLogService()->record(
                action: 'population.family.created',
                user: $this->actor($userId),
                auditable: $family,
                newValues: $this->familyAuditValues($family),
                tenantId: $tenantId,
            );

            return $family;
        });
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

        if ($this->isFamilyHead($citizen->id) || ($status === 'active' && $this->hasActiveMembership($citizen->id))) {
            throw ValidationException::withMessages([
                'hubungan_dalam_keluarga' => 'Warga ini sudah menjadi anggota aktif KK lain atau merupakan kepala keluarga.',
            ]);
        }

        DB::transaction(function () use ($tenantId, $family, $citizen, $data): void {
            $member = FamilyMember::query()
                ->where('family_id', $family->id)
                ->where('citizen_id', $citizen->id)
                ->first();
            $oldValues = $member ? $this->familyMemberAuditValues($member) : null;

            $member = FamilyMember::updateOrCreate(
                ['family_id' => $family->id, 'citizen_id' => $citizen->id],
                [
                    'hubungan_dalam_keluarga' => $data['hubungan_dalam_keluarga'],
                    'status' => $data['status'],
                ]
            );

            $this->auditLogService()->record(
                action: $oldValues === null
                    ? 'population.family_member.created'
                    : 'population.family_member.updated',
                user: $this->actor(),
                auditable: $member,
                oldValues: $oldValues,
                newValues: $this->familyMemberAuditValues($member->refresh()),
                tenantId: $tenantId,
            );
        });
    }

    public function removeMember(string $tenantId, string $familyId, string $citizenId): void
    {
        $family = $this->findForTenant($tenantId, $familyId);

        DB::transaction(function () use ($tenantId, $family, $citizenId): void {
            $member = FamilyMember::query()
                ->where('family_id', $family->id)
                ->where('citizen_id', $citizenId)
                ->first();

            if ($member === null) {
                return;
            }

            $oldValues = $this->familyMemberAuditValues($member);
            $member->delete();

            $this->auditLogService()->record(
                action: 'population.family_member.deleted',
                user: $this->actor(),
                auditable: $member,
                oldValues: $oldValues,
                tenantId: $tenantId,
            );
        });
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

    private function memberCandidates(string $tenantId, string $search)
    {
        return Citizen::query()
            ->where('tenant_id', $tenantId)
            ->whereDoesntHave('activeFamilyMembership')
            ->whereDoesntHave('headedFamilies')
            ->when($search !== '', fn ($q) => $q->where(function ($query) use ($search): void {
                $query->whereRaw('LOWER(nik) LIKE LOWER(?)', ['%'.$search.'%'])
                    ->orWhereRaw('LOWER(nama_lengkap) LIKE LOWER(?)', ['%'.$search.'%']);
            }))
            ->orderBy('nama_lengkap')
            ->limit(10)
            ->get(['id', 'nik', 'nama_lengkap']);
    }

    private function isFamilyHead(string $citizenId): bool
    {
        return Family::query()->where('head_citizen_id', $citizenId)->exists();
    }

    private function hasActiveMembership(string $citizenId): bool
    {
        return FamilyMember::query()
            ->where('citizen_id', $citizenId)
            ->where('status', 'active')
            ->exists();
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

    private function auditLogService(): AuditLogService
    {
        return app(AuditLogService::class);
    }

    private function actor(int|string|null $userId = null): ?User
    {
        $user = Auth::user();

        if ($user instanceof User) {
            return $user;
        }

        return $userId !== null ? User::query()->find($userId) : null;
    }

    private function familyAuditValues(Family $family): array
    {
        return [
            'no_kk' => $family->no_kk,
            'head_citizen_id' => $family->head_citizen_id,
            'alamat' => $family->alamat,
            'rt' => $family->rt,
            'rw' => $family->rw,
            'kelurahan' => $family->kelurahan,
            'kecamatan' => $family->kecamatan,
            'kabupaten_kota' => $family->kabupaten_kota,
            'provinsi' => $family->provinsi,
            'kode_pos' => $family->kode_pos,
            'status' => $family->status,
            'tenant_id' => $family->tenant_id,
        ];
    }

    private function familyMemberAuditValues(FamilyMember $member): array
    {
        return [
            'family_id' => $member->family_id,
            'citizen_id' => $member->citizen_id,
            'hubungan_dalam_keluarga' => $member->hubungan_dalam_keluarga,
            'status' => $member->status,
        ];
    }
}
