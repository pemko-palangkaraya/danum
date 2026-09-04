<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\CitizenAddress;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CitizenService
{
    public function query(string $tenantId, string $search = ''): Builder
    {
        return Citizen::query()
            ->where('tenant_id', $tenantId)
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query
                    ->where('nik', 'ilike', '%' . $search . '%')
                    ->orWhere('nama_lengkap', 'ilike', '%' . $search . '%')
            ))
            ->orderBy('nama_lengkap');
    }

    public function paginate(string $tenantId, string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return $this->query($tenantId, $search)->paginate($perPage);
    }

    public function findForTenant(string $tenantId, string $id): Citizen
    {
        return $this->query($tenantId)->findOrFail($id);
    }

    public function find(string $id): Citizen
    {
        return Citizen::query()->findOrFail($id);
    }

    public function tenants(): \Illuminate\Support\Collection
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function loadDetail(Citizen $citizen): Citizen
    {
        return $citizen->load([
            'familyMemberships.family.headCitizen',
            'familyMemberships.family.activeMembers.citizen',
            'addresses' => fn ($query) => $query->latest('berlaku_mulai')->latest('created_at'),
            'populationEvents' => fn ($query) => $query->latest('event_date'),
        ]);
    }

    public function loadAddresses(Citizen $citizen): Citizen
    {
        return $citizen->load([
            'addresses' => fn ($query) => $query->latest('berlaku_mulai')->latest('created_at'),
        ]);
    }

    public function addAddress(Citizen $citizen, array $input): CitizenAddress
    {
        $data = Validator::make($input, [
            'alamat' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:3'],
            'rw' => ['nullable', 'string', 'max:3'],
            'kelurahan' => ['nullable', 'string', 'max:255'],
            'kecamatan' => ['nullable', 'string', 'max:255'],
            'kabupaten_kota' => ['nullable', 'string', 'max:255'],
            'provinsi' => ['nullable', 'string', 'max:255'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'jenis_alamat' => ['required', 'string', 'max:30'],
            'berlaku_mulai' => ['nullable', 'date'],
        ])->validate();

        $data['citizen_id'] = $citizen->id;

        return CitizenAddress::create($data);
    }

    public function save(string $tenantId, array $data, ?string $editingId, int|string $userId): Citizen
    {
        $validated = Validator::make($data, $this->rules($tenantId, $editingId))->validate();
        $validated['tenant_id'] = $tenantId;
        $validated['updated_by'] = $userId;

        if ($editingId !== null) {
            $citizen = $this->findForTenant($tenantId, $editingId);
            $citizen->update($validated);
            return $citizen->refresh();
        }

        $validated['created_by'] = $userId;
        return Citizen::create($validated);
    }

    public function rules(string $tenantId, ?string $editingId = null): array
    {
        return [
            'nik' => ['required', 'digits:16', Rule::unique('citizens', 'nik')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($editingId)],
            'nama_lengkap' => ['required', 'string', 'max:255'], 'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'], 'jenis_kelamin' => ['nullable', 'in:male,female'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O,unknown'], 'agama' => ['nullable', 'string', 'max:40'],
            'status_perkawinan' => ['nullable', 'string', 'max:30'], 'pendidikan' => ['nullable', 'string', 'max:100'],
            'pekerjaan' => ['nullable', 'string', 'max:150'], 'kewarganegaraan' => ['required', 'string', 'max:50'],
            'no_passport' => ['nullable', 'string', 'max:50'], 'no_kitap' => ['nullable', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'], 'nik_ayah' => ['nullable', 'digits:16'],
            'nama_ibu' => ['nullable', 'string', 'max:255'], 'nik_ibu' => ['nullable', 'digits:16'],
            'status_kependudukan' => ['required', 'string', 'max:30'],
        ];
    }
}
