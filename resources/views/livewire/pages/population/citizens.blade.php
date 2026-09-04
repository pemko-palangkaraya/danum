<?php

declare(strict_types=1);

use App\Models\Citizen;
use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
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

    public function mount(): void
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
                $citizen = Citizen::query()->findOrFail((string) $editId);
                $this->selectedTenantId = $citizen->tenant_id;
            }

            $this->edit((string) $editId);
        }
    }

    public function updatedSearch(): void
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

    private function query()
    {
        return Citizen::query()
            ->where('tenant_id', $this->tenantId())
            ->when($this->search !== '', fn ($query) => $query->where(
                fn ($query) => $query
                    ->where('nik', 'ilike', '%' . $this->search . '%')
                    ->orWhere('nama_lengkap', 'ilike', '%' . $this->search . '%')
            ))
            ->orderBy('nama_lengkap');
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
        $citizen = $this->query()->findOrFail($id);

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
        $tenantId = $this->tenantId();

        $data = Validator::make($this->only($this->fields()), $this->rules($tenantId))->validate();
        $data['tenant_id'] = $tenantId;
        $data['updated_by'] = auth()->id();

        if ($this->editingId) {
            $this->query()->findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by'] = auth()->id();
            Citizen::create($data);
        }

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

    private function rules(string $tenantId): array
    {
        return [
            'nik' => [
                'required', 'digits:16',
                Rule::unique('citizens', 'nik')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($this->editingId),
            ],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:male,female'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O,unknown'],
            'agama' => ['nullable', 'string', 'max:40'],
            'status_perkawinan' => ['nullable', 'string', 'max:30'],
            'pendidikan' => ['nullable', 'string', 'max:100'],
            'pekerjaan' => ['nullable', 'string', 'max:150'],
            'kewarganegaraan' => ['required', 'string', 'max:50'],
            'no_passport' => ['nullable', 'string', 'max:50'],
            'no_kitap' => ['nullable', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nik_ayah' => ['nullable', 'digits:16'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'nik_ibu' => ['nullable', 'digits:16'],
            'status_kependudukan' => ['required', 'string', 'max:30'],
        ];
    }

    public function with(): array
    {
        return [
            'citizens' => ($this->selectedTenantId || auth()->user()->tenant_id) ? $this->query()->paginate($this->perPage) : collect(),
            'tenants' => auth()->user()->isSuperAdmin() ? Tenant::query()->orderBy('name')->get(['id', 'name', 'code']) : collect(),
        ];
    }
};
?>
<div class="space-y-6">
    @include('livewire.pages.population.partials.citizens-header')
    @include('livewire.pages.population.partials.citizens-filters')

    @if($selectedTenantId || auth()->user()->tenant_id)
        @if($showForm)
            @include('livewire.pages.population.partials.citizens-form')
        @endif

        @include('livewire.pages.population.partials.citizens-table')
    @endif
</div>
