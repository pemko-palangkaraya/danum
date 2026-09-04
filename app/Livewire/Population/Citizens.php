<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Services\CitizenService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Citizens extends Component
{
    use WithPagination;

    public int $perPage = 10;
    public string $search = '';
    public bool $showForm = false;
    public ?string $editingId = null;
    public ?string $selectedTenantId = null;

    public string $nik = '';
    public string $nama_lengkap = '';
    public string $tempat_lahir = '';
    public string $tanggal_lahir = '';
    public string $jenis_kelamin = '';
    public string $golongan_darah = '';
    public string $agama = '';
    public string $status_perkawinan = '';
    public string $pendidikan = '';
    public string $pekerjaan = '';
    public string $kewarganegaraan = 'WNI';
    public string $no_passport = '';
    public string $no_kitap = '';
    public string $nama_ayah = '';
    public string $nik_ayah = '';
    public string $nama_ibu = '';
    public string $nik_ibu = '';
    public string $status_kependudukan = 'active';

    public function mount(CitizenService $citizenService): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);

        if (! auth()->user()->isSuperAdmin()) {
            abort_unless(auth()->user()->tenant_id, 403);
            $this->selectedTenantId = auth()->user()->tenant_id;
        }

        $editId = request()->query('edit');
        if ($editId !== null && $editId !== '') {
            $this->authorizeManage();

            if (auth()->user()->isSuperAdmin()) {
                $citizen = $citizenService->find((string) $editId);
                $this->selectedTenantId = $citizen->tenant_id;
            }

            $this->edit((string) $editId);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedTenantId(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->resetPage();
        $this->resetForm();
    }

    private function tenantId(): string
    {
        $id = auth()->user()->isSuperAdmin() ? $this->selectedTenantId : auth()->user()->tenant_id;
        abort_unless($id, 422);

        return (string) $id;
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);
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
        $citizen = app(CitizenService::class)->findForTenant($this->tenantId(), $id);

        foreach ($this->fields() as $field) {
            $this->{$field} = (string) ($citizen->{$field} ?? '');
        }

        $this->tanggal_lahir = $citizen->tanggal_lahir?->format('Y-m-d') ?? '';
        $this->editingId = $citizen->id;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorizeManage();

        app(CitizenService::class)->save(
            $this->tenantId(),
            $this->only($this->fields()),
            $this->editingId,
            auth()->id(),
        );

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Data warga berhasil disimpan.');
    }

    public function resetForm(): void
    {
        foreach ($this->fields() as $field) {
            $this->{$field} = '';
        }

        $this->kewarganegaraan = 'WNI';
        $this->status_kependudukan = 'active';
        $this->editingId = null;
        $this->showForm = false;
        $this->resetValidation();
    }

    private function fields(): array
    {
        return [
            'nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir',
            'jenis_kelamin', 'golongan_darah', 'agama', 'status_perkawinan',
            'pendidikan', 'pekerjaan', 'kewarganegaraan', 'no_passport',
            'no_kitap', 'nama_ayah', 'nik_ayah', 'nama_ibu', 'nik_ibu',
            'status_kependudukan',
        ];
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $canManage = $user->hasPermission('population.manage');
        $tenantSelected = (bool) ($this->selectedTenantId || $user->tenant_id);
        $service = app(CitizenService::class);

        return view('livewire.pages.population.citizens', [
            'citizens' => $tenantSelected
                ? $service->paginate($this->tenantId(), $this->search, $this->perPage)
                : collect(),
            'tenants' => $isSuperAdmin ? $service->tenants() : collect(),
            'isSuperAdmin' => $isSuperAdmin,
            'canManage' => $canManage,
            'tenantSelected' => $tenantSelected,
            'detailRoute' => $isSuperAdmin
                ? 'population.admin.citizens.show'
                : 'population.citizens.show',
        ]);
    }
}
