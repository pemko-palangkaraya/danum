<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Livewire\Concerns\WithStandardTablePagination;
use App\Services\FamilyService;
use App\Services\PopulationLocationService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Families extends Component
{
    use WithStandardTablePagination;

    public string $search = '';
    public string $headSearch = '';
    public string $memberSearch = '';
    public string $memberRelationship = '';
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

        if (! auth()->user()->isSuperAdmin()) {
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

    public function updatedProvinsi(): void
    {
        $this->kabupaten_kota = '';
        $this->kecamatan = '';
        $this->kelurahan = '';
        $this->kode_pos = '';
    }

    public function updatedKabupatenKota(): void
    {
        $this->kecamatan = '';
        $this->kelurahan = '';
        $this->kode_pos = '';
    }

    public function updatedKecamatan(): void
    {
        $this->kelurahan = '';
        $this->kode_pos = '';
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
        $family = app(FamilyService::class)->findForTenant($this->tenantId(), $id);

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

    public function selectHead(string $citizenId): void
    {
        $citizen = app(FamilyService::class)->findCitizen($this->tenantId(), $citizenId);
        $this->head_citizen_id = $citizen->id;
        $this->headSearch = $citizen->nama_lengkap;
    }

    public function resetHead(): void
    {
        $this->head_citizen_id = '';
        $this->headSearch = '';
    }

    public function save(): void
    {
        $this->authorizeManage();
        app(FamilyService::class)->save(
            $this->tenantId(),
            $this->only($this->formFields()),
            $this->editingId,
            auth()->id(),
        );

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Data KK berhasil disimpan.');
    }

    public function addMember(string $familyId, string $citizenId, string $hubunganDalamKeluarga, string $status = 'active'): void
    {
        $this->authorizeManage();
        app(FamilyService::class)->addMember(
            $this->tenantId(),
            $familyId,
            $citizenId,
            $hubunganDalamKeluarga,
            $status,
        );

        $this->memberSearch = '';
        $this->memberRelationship = '';
        $this->dispatch('toast', type: 'success', message: 'Anggota keluarga berhasil ditambahkan.');
    }

    public function removeMember(string $familyId, string $citizenId): void
    {
        $this->authorizeManage();
        app(FamilyService::class)->removeMember($this->tenantId(), $familyId, $citizenId);
        $this->dispatch('toast', type: 'success', message: 'Anggota keluarga berhasil dihapus.');
    }

    public function showDetail(string $id): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);
        app(FamilyService::class)->findForTenant($this->tenantId(), $id);
        $this->detailId = $id;
        $this->memberSearch = '';
        $this->memberRelationship = '';
    }

    public function closeDetail(): void
    {
        $this->detailId = null;
        $this->memberSearch = '';
        $this->memberRelationship = '';
    }

    public function render(): View
    {
        $service = app(FamilyService::class);
        $locationService = app(PopulationLocationService::class);
        $tenantId = $this->tenantIdForQuery();
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $canManage = $user?->hasPermission('population.manage') ?? false;
        $hasTenant = $tenantId !== '';

        $families = $hasTenant
            ? $service->paginate($tenantId, $this->search, $this->perPage)
            : collect();
        $tenants = $isSuperAdmin ? $service->tenants() : collect();
        $detail = $this->detailId && $hasTenant
            ? $service->findDetail($tenantId, $this->detailId)
            : null;
        $headCitizens = $hasTenant && $this->headSearch !== ''
            ? $service->findHeadCandidates($tenantId, $this->headSearch)
            : collect();
        $memberCandidates = $hasTenant && $this->memberSearch !== ''
            ? $service->findMemberCandidates($tenantId, $this->memberSearch)
            : collect();
        $selectedHead = $hasTenant && $this->head_citizen_id !== ''
            ? $service->selectedHead($tenantId, $this->head_citizen_id)
            : null;
        $locationOptions = $hasTenant && $this->showForm
            ? $locationService->optionsForTenant($tenantId, $this->provinsi, $this->kabupaten_kota, $this->kecamatan)
            : $locationService->emptyOptions();

        return view('livewire.population.families', [
            'families' => $families,
            'tenants' => $tenants,
            'detail' => $detail,
            'memberCandidates' => $memberCandidates,
            'selectedHead' => $selectedHead,
            'headCitizens' => $headCitizens,
            'locationOptions' => $locationOptions,
            'canManage' => $canManage,
            'isSuperAdmin' => $isSuperAdmin,
            'hasTenant' => $hasTenant,
        ]);
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);
    }

    private function tenantId(): string
    {
        if (auth()->user()?->isSuperAdmin()) {
            abort_unless($this->selectedTenantId, 422);
            return $this->selectedTenantId;
        }

        return (string) auth()->user()->tenant_id;
    }

    private function tenantIdForQuery(): string
    {
        return auth()->user()?->isSuperAdmin()
            ? (string) ($this->selectedTenantId ?? '')
            : (string) auth()->user()->tenant_id;
    }

    private function formFields(): array
    {
        return [
            'no_kk', 'head_citizen_id', 'alamat', 'rt', 'rw', 'kelurahan',
            'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos', 'status',
        ];
    }

    private function addressFields(): array
    {
        return ['alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos'];
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->showForm = false;
        $this->no_kk = '';
        $this->head_citizen_id = '';
        $this->alamat = '';
        $this->rt = '';
        $this->rw = '';
        $this->kelurahan = '';
        $this->kecamatan = '';
        $this->kabupaten_kota = '';
        $this->provinsi = '';
        $this->kode_pos = '';
        $this->status = 'active';
        $this->headSearch = '';
        $this->memberRelationship = '';

        $tenantId = $this->tenantIdForQuery();
        if ($tenantId !== '') {
            $defaults = app(PopulationLocationService::class)->defaultsForTenant($tenantId);
            $this->provinsi = $defaults['province'];
            $this->kabupaten_kota = $defaults['city'];
            $this->kecamatan = $defaults['district'];
            $this->kelurahan = $defaults['village'];
        }
    }
}
