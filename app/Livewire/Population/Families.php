<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Families extends Component
{
    use WithPagination;

    public string $search = '';
    public string $headSearch = '';
    public string $memberSearch = '';
    public int $perPage = 10;
    public ?string $selectedTenantId = null;
    public ?string $editingId = null;
    public bool $showForm = false;
    public string $no_kk = '';
    public string $head_citizen_id = '';
    public string $alamat = '';
    public string $rt = '';
    public string $rw = '';
    public string $kelurahan = '';
    public string $kecamatan = '';
    public string $kabupaten_kota = '';
    public string $provinsi = '';
    public string $kode_pos = '';
    public string $status = 'active';
    public ?string $detailId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);

        if (!auth()->user()->isSuperAdmin()) {
            abort_unless(auth()->user()->tenant_id, 403);
            $this->selectedTenantId = auth()->user()->tenant_id;
        }
    }

    public function updatedSelectedTenantId(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->resetPage();
        $this->resetForm();
        $this->detailId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorizeManage();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $this->authorizeManage();
        $family = $this->familiesQuery()->findOrFail($id);

        $this->editingId = $family->id;
        foreach ($this->addressFields() as $field) {
            $this->{$field} = (string) ($family->{$field} ?? '');
        }
        $this->no_kk = (string) $family->no_kk;
        $this->status = (string) $family->status;
        $this->head_citizen_id = (string) ($family->head_citizen_id ?? '');
        $this->headSearch = $family->headCitizen?->nama_lengkap ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeManage();
        $tenantId = $this->tenantId();
        $data = Validator::make($this->only($this->formFields()), $this->rules($tenantId))->validate();

        if (!empty($data['head_citizen_id'])) {
            abort_unless(
                Citizen::whereKey($data['head_citizen_id'])->where('tenant_id', $tenantId)->exists(),
                422
            );
        }

        $data['tenant_id'] = $tenantId;
        $data['updated_by'] = auth()->id();

        if ($this->editingId) {
            $this->familiesQuery()->findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by'] = auth()->id();
            Family::create($data);
        }

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Data KK berhasil disimpan.');
    }

    public function show(string $id): void
    {
        $this->detailId = $this->familiesQuery()->findOrFail($id)->id;
        $this->showForm = false;
        $this->memberSearch = '';
    }

    public function addMember(string $citizenId): void
    {
        $this->authorizeManage();
        $family = $this->familiesQuery()->findOrFail($this->detailId);
        $citizen = Citizen::where('tenant_id', $this->tenantId())->findOrFail($citizenId);

        abort_if(
            FamilyMember::where('citizen_id', $citizen->id)->where('status', 'active')->exists(),
            422,
            'Warga sudah memiliki KK aktif.'
        );

        FamilyMember::create([
            'family_id' => $family->id,
            'citizen_id' => $citizen->id,
            'hubungan_dalam_keluarga' => 'family_member',
            'urutan' => $family->activeMembers()->count() + 1,
            'tanggal_mulai' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->memberSearch = '';
    }

    public function removeMember(string $memberId): void
    {
        $this->authorizeManage();

        $member = FamilyMember::whereHas(
            'family',
            fn ($query) => $query->where('tenant_id', $this->tenantId())
        )->findOrFail($memberId);

        $member->update([
            'status' => 'inactive',
            'tanggal_selesai' => now()->toDateString(),
        ]);
    }

    public function selectHead(string $citizenId): void
    {
        $this->authorizeManage();
        $citizen = Citizen::where('tenant_id', $this->tenantId())->findOrFail($citizenId);

        $this->head_citizen_id = $citizen->id;
        $this->headSearch = $citizen->nama_lengkap;
    }

    public function resetHead(): void
    {
        $this->head_citizen_id = '';
        $this->headSearch = '';
    }

    public function resetForm(): void
    {
        foreach ($this->formFields() as $field) {
            $this->{$field} = '';
        }

        $this->status = 'active';
        $this->editingId = null;
        $this->showForm = false;
        $this->headSearch = '';
        $this->memberSearch = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $tenant = $this->selectedTenantId || auth()->user()->tenant_id;
        $headCitizens = collect();
        $memberCandidates = collect();

        if ($tenant && $this->headSearch !== '') {
            $headCitizens = $this->citizenSearch($this->headSearch)->get();
        }

        if ($tenant && $this->detailId && $this->memberSearch !== '') {
            $activeMemberIds = FamilyMember::where('family_id', $this->detailId)
                ->where('status', 'active')
                ->pluck('citizen_id');

            $memberCandidates = $this->citizenSearch($this->memberSearch)
                ->whereNotIn('id', $activeMemberIds)
                ->get();
        }

        return view('livewire.population.families', [
            'families' => $tenant ? $this->familiesQuery()->paginate($this->perPage) : collect(),
            'tenants' => auth()->user()->isSuperAdmin()
                ? Tenant::orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
            'headCitizens' => $headCitizens,
            'memberCandidates' => $memberCandidates,
            'selectedHead' => $this->selectedHead($tenant),
            'detail' => $this->detailId ? $this->familiesQuery()->find($this->detailId) : null,
        ]);
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
    }

    private function tenantId(): string
    {
        $id = auth()->user()->isSuperAdmin()
            ? $this->selectedTenantId
            : auth()->user()->tenant_id;

        abort_unless($id, 422);
        return (string) $id;
    }

    private function familiesQuery()
    {
        return Family::query()
            ->where('tenant_id', $this->tenantId())
            ->with(['headCitizen', 'activeMembers.citizen'])
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('no_kk', 'ilike', '%' . $this->search . '%')
                    ->orWhereHas('headCitizen', fn ($query) =>
                        $query->where('nama_lengkap', 'ilike', '%' . $this->search . '%')
                    )
            ))
            ->orderBy('no_kk');
    }

    private function citizenSearch(string $search)
    {
        return Citizen::query()
            ->where('tenant_id', $this->tenantId())
            ->where(fn ($query) => $query
                ->where('nama_lengkap', 'ilike', '%' . $search . '%')
                ->orWhere('nik', 'ilike', '%' . $search . '%')
            )
            ->orderBy('nama_lengkap')
            ->limit(15)
            ->select(['id', 'nik', 'nama_lengkap']);
    }

    private function selectedHead(?string $tenant): ?Citizen
    {
        if (!$tenant || !$this->head_citizen_id) {
            return null;
        }

        return Citizen::where('tenant_id', $this->tenantId())->find($this->head_citizen_id);
    }

    private function formFields(): array
    {
        return [
            'no_kk', 'head_citizen_id', 'alamat', 'rt', 'rw',
            'kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos',
        ];
    }

    private function addressFields(): array
    {
        return [
            'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan',
            'kabupaten_kota', 'provinsi', 'kode_pos',
        ];
    }

    private function rules(string $tenantId): array
    {
        return [
            'no_kk' => ['required', 'digits:16', 'unique:families,no_kk,' . ($this->editingId ?? 'NULL') . ',id,tenant_id,' . $tenantId],
            'head_citizen_id' => ['nullable', 'uuid', 'exists:citizens,id'],
            'alamat' => ['required', 'string', 'max:500'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kabupaten_kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'status' => ['required', 'string', 'max:30'],
        ];
    }
}
