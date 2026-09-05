<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\CitizenAddress;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'activeFamilyMembership.family.headCitizen',
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
            'kelurahan' => ['required', 'string', 'max:255'],
            'kecamatan' => ['required', 'string', 'max:255'],
            'kabupaten_kota' => ['required', 'string', 'max:255'],
            'provinsi' => ['required', 'string', 'max:255'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'jenis_alamat' => ['required', 'string', 'max:30'],
            'berlaku_mulai' => ['nullable', 'date'],
        ])->validate();

        if (! app(PopulationLocationService::class)->existsForTenant(
            (string) $citizen->tenant_id,
            $data['provinsi'],
            $data['kabupaten_kota'],
            $data['kecamatan'],
            $data['kelurahan'],
        )) {
            throw ValidationException::withMessages([
                'kelurahan' => 'Wilayah harus berada dalam cakupan tenant yang sedang digunakan.',
            ]);
        }

        $data['citizen_id'] = $citizen->id;

        return DB::transaction(function () use ($citizen, $data): CitizenAddress {
            $address = CitizenAddress::create($data);

            $this->auditLogService()->record(
                action: 'population.citizen_address.created',
                user: $this->actor(),
                auditable: $address,
                newValues: $this->addressAuditValues($address),
                tenantId: (string) $citizen->tenant_id,
            );

            return $address;
        });
    }

    public function save(string $tenantId, array $data, ?string $editingId, int|string $userId): Citizen
    {
        $data = $this->normalizeInput($data);
        $validated = Validator::make($data, $this->rules($tenantId, $editingId))->validate();
        $validated['tenant_id'] = $tenantId;
        $validated['updated_by'] = $userId;

        return DB::transaction(function () use ($tenantId, $validated, $editingId, $userId): Citizen {
            if ($editingId !== null) {
                $citizen = $this->findForTenant($tenantId, $editingId);
                $oldValues = $this->citizenAuditValues($citizen);
                $citizen->update($validated);
                $citizen = $citizen->refresh();

                $this->auditLogService()->record(
                    action: 'population.citizen.updated',
                    user: $this->actor($userId),
                    auditable: $citizen,
                    oldValues: $oldValues,
                    newValues: $this->citizenAuditValues($citizen),
                    tenantId: $tenantId,
                );

                return $citizen;
            }

            $validated['created_by'] = $userId;
            $citizen = Citizen::create($validated);

            $this->auditLogService()->record(
                action: 'population.citizen.created',
                user: $this->actor($userId),
                auditable: $citizen,
                newValues: $this->citizenAuditValues($citizen),
                tenantId: $tenantId,
            );

            return $citizen;
        });
    }

    private function normalizeInput(array $data): array
    {
        foreach ([
            'tempat_lahir', 'tanggal_lahir', 'tanggal_meninggal', 'jenis_kelamin', 'golongan_darah', 'agama',
            'status_perkawinan', 'pendidikan', 'pekerjaan', 'no_passport', 'no_kitap',
            'nama_ayah', 'nik_ayah', 'nama_ibu', 'nik_ibu',
        ] as $field) {
            if (array_key_exists($field, $data) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
                if ($data[$field] === '') {
                    $data[$field] = null;
                }
            }
        }

        if (($data['golongan_darah'] ?? null) === 'unknown') {
            $data['golongan_darah'] = null;
        }

        return $data;
    }

    public function rules(string $tenantId, ?string $editingId = null): array
    {
        return [
            'nik' => ['required', 'digits:16', Rule::unique('citizens', 'nik')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($editingId)],
            'nama_lengkap' => ['required', 'string', 'max:255'], 'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'], 'tanggal_meninggal' => ['nullable', 'date', 'after_or_equal:tanggal_lahir', 'before_or_equal:today'],
            'jenis_kelamin' => ['nullable', 'in:male,female'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'], 'agama' => ['nullable', 'string', 'max:40'],
            'status_perkawinan' => ['nullable', 'string', 'max:30'], 'pendidikan' => ['nullable', 'string', 'max:100'],
            'pekerjaan' => ['nullable', 'string', 'max:150'], 'kewarganegaraan' => ['required', 'string', 'max:50'],
            'no_passport' => ['nullable', 'string', 'max:50'], 'no_kitap' => ['nullable', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'], 'nik_ayah' => ['nullable', 'digits:16'],
            'nama_ibu' => ['nullable', 'string', 'max:255'], 'nik_ibu' => ['nullable', 'digits:16'],
            'status_kependudukan' => ['required', 'string', 'max:30'],
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

    private function citizenAuditValues(Citizen $citizen): array
    {
        return [
            'nik' => $citizen->nik,
            'nama_lengkap' => $citizen->nama_lengkap,
            'tempat_lahir' => $citizen->tempat_lahir,
            'tanggal_lahir' => $citizen->tanggal_lahir?->format('Y-m-d'),
            'tanggal_meninggal' => $citizen->tanggal_meninggal?->format('Y-m-d'),
            'jenis_kelamin' => $citizen->jenis_kelamin,
            'golongan_darah' => $citizen->golongan_darah,
            'agama' => $citizen->agama,
            'status_perkawinan' => $citizen->status_perkawinan,
            'pendidikan' => $citizen->pendidikan,
            'pekerjaan' => $citizen->pekerjaan,
            'kewarganegaraan' => $citizen->kewarganegaraan,
            'no_passport' => $citizen->no_passport,
            'no_kitap' => $citizen->no_kitap,
            'nama_ayah' => $citizen->nama_ayah,
            'nik_ayah' => $citizen->nik_ayah,
            'nama_ibu' => $citizen->nama_ibu,
            'nik_ibu' => $citizen->nik_ibu,
            'status_kependudukan' => $citizen->status_kependudukan,
            'tenant_id' => $citizen->tenant_id,
        ];
    }

    private function addressAuditValues(CitizenAddress $address): array
    {
        return [
            'citizen_id' => $address->citizen_id,
            'alamat' => $address->alamat,
            'rt' => $address->rt,
            'rw' => $address->rw,
            'kelurahan' => $address->kelurahan,
            'kecamatan' => $address->kecamatan,
            'kabupaten_kota' => $address->kabupaten_kota,
            'provinsi' => $address->provinsi,
            'kode_pos' => $address->kode_pos,
            'jenis_alamat' => $address->jenis_alamat,
            'berlaku_mulai' => $address->berlaku_mulai?->format('Y-m-d'),
            'berlaku_sampai' => $address->berlaku_sampai?->format('Y-m-d'),
        ];
    }
}
