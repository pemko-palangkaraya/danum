<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
    public string $memberRelationship = '';
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

    public function selectHead(string $citizenId): void
    {
        $citizen = Citizen::query()
            ->whereKey($citizenId)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();

        $this->head_citizen_id = $citizen->id;
        $this->headSearch = $citizen->nama_lengkap;
    }

    public function resetHead(): void
    {
        $this->head_citizen_id = '';
        $this->headSearch = '';
    }

    public function getSelectedHeadProperty(): ?Citizen
    {
        if ($this->head_citizen_id === '') {
            return null;
        }

        return Citizen::query()
            ->whereKey($this->head_citizen_id)
            ->where('tenant_id', $this->tenantIdForQuery())
            ->first();
    }

    public function getHeadCitizensProperty()
    {
        return $this->headCandidates();
    }

    public function save(): void
    {
        $this->authorizeManage();
        $tenantId = $this->tenantId();
        $input = $this->only($this->formFields());
        $input['head_citizen_id'] = $input['head_citizen_id'] ?: null;

        $data = Validator::make($input, $this->rules($tenantId))->validate();

        if (! empty($data['head_citizen_id'])) {
            abort_unless(
                Citizen::query()
                    ->whereKey($data['head_citizen_id'])
                    ->where('tenant_id', $tenantId)
                    ->exists(),
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

    public function addMember(string $familyId, string $citizenId, string $hubunganDalamKeluarga, string $status = 'active'): void
    {
        $this->authorizeManage();
        $tenantId = $this->tenantId();
        $family = $this->familiesQuery()->findOrFail($familyId);
        $citizen = Citizen::query()->whereKey($citizenId)->where('tenant_id', $tenantId)->firstOrFail();

        $data = Validator::make([
            'hubungan_dalam_keluarga' => $hubunganDalamKeluarga,
        ], [
            'hubungan_dalam_keluarga' => ['required', 'string', 'max:40'],
        ])->validate();

        FamilyMember::updateOrCreate(
            ['family_id' => $family->id, 'citizen_id' => $citizen->id],
            ['hubungan_dalam_keluarga' => $data['hubungan_dalam_keluarga'], 'status' => $status]
        );

        $this->memberSearch = '';
        $this->memberRelationship = '';
        $this->dispatch('toast', type: 'success', message: 'Anggota keluarga berhasil ditambahkan.');
    }

    public function removeMember(string $familyId, string $citizenId): void
    {
        $this->authorizeManage();
        $family = $this->familiesQuery()->findOrFail($familyId);
        FamilyMember::query()->where('family_id', $family->id)->where('citizen_id', $citizenId)->delete();
        $this->dispatch('toast', type: 'success', message: 'Anggota keluarga berhasil dihapus.');
    }

    public function showDetail(string $id): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);
        $this->familiesQuery()->findOrFail($id);
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
        $families = $this->familiesQuery()
            ->with('headCitizen')
            ->withCount('members')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('no_kk', 'like', '%'.$this->search.'%')
                        ->orWhere('alamat', 'like', '%'.$this->search.'%')
                        ->orWhere('kelurahan', 'like', '%'.$this->search.'%')
                        ->orWhere('kecamatan', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate($this->perPage);

        $tenants = auth()->user()?->isSuperAdmin()
            ? Tenant::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $detail = $this->detailId
            ? $this->familiesQuery()->with(['headCitizen', 'members.citizen'])->find($this->detailId)
            : null;

        $headCandidates = $this->headCandidates();
        $memberCandidates = $this->memberCandidates();
        $selectedHead = $this->getSelectedHeadProperty();
        $headCitizens = $headCandidates;

        return view('livewire.population.families', compact(
            'families', 'tenants', 'detail', 'headCandidates', 'memberCandidates',
            'selectedHead', 'headCitizens'
        ));
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

    private function familiesQuery()
    {
        $tenantId = $this->tenantIdForQuery();

        if ($tenantId === '') {
            return Family::query()->whereRaw('1 = 0');
        }

        return Family::query()->where('tenant_id', $tenantId);
    }

    private function tenantIdForQuery(): string
    {
        return auth()->user()?->isSuperAdmin()
            ? (string) ($this->selectedTenantId ?? '')
            : (string) auth()->user()->tenant_id;
    }

    private function headCandidates()
    {
        $tenantId = $this->tenantIdForQuery();
        if ($tenantId === '') {
            return collect();
        }

        return Citizen::query()
            ->where('tenant_id', $tenantId)
            ->when($this->headSearch !== '', fn ($q) => $q->where(function ($query) {
                $query->where('nik', 'like', '%'.$this->headSearch.'%')
                    ->orWhere('nama_lengkap', 'like', '%'.$this->headSearch.'%');
            }))
            ->orderBy('nama_lengkap')
            ->limit(10)
            ->get(['id', 'nik', 'nama_lengkap']);
    }

    private function memberCandidates()
    {
        $tenantId = $this->tenantIdForQuery();
        if ($tenantId === '') {
            return collect();
        }

        return Citizen::query()
            ->where('tenant_id', $tenantId)
            ->when($this->memberSearch !== '', fn ($q) => $q->where(function ($query) {
                $query->where('nik', 'like', '%'.$this->memberSearch.'%')
                    ->orWhere('nama_lengkap', 'like', '%'.$this->memberSearch.'%');
            }))
            ->orderBy('nama_lengkap')
            ->limit(10)
            ->get(['id', 'nik', 'nama_lengkap']);
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

    private function rules(string $tenantId): array
    {
        return [
            'no_kk' => [
                'required', 'string', 'size:16',
                Rule::unique('families', 'no_kk')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($this->editingId),
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

    private function resetForm(): void
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
    }
}
