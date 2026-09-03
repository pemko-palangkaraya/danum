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
        $id = auth()->user()->isSuperAdmin()
            ? $this->selectedTenantId
            : auth()->user()->tenant_id;

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
                'required',
                'digits:16',
                Rule::unique('citizens', 'nik')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($this->editingId),
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
            'citizens' => ($this->selectedTenantId || auth()->user()->tenant_id)
                ? $this->query()->paginate($this->perPage)
                : collect(),
            'tenants' => auth()->user()->isSuperAdmin()
                ? Tenant::query()->orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
        ];
    }
};
?>
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="text-sm font-medium text-slate-500">Kependudukan</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Data Warga</h1><p class="mt-1 text-sm text-slate-500">Master data penduduk yang terdaftar dalam tenant.</p></div>
        <div class="flex flex-wrap gap-2">
            @if(auth()->user()->hasPermission('population.view') && (!auth()->user()->isSuperAdmin() || $selectedTenantId))
                <a href="{{ route('population.citizens.export', ['format'=>'xlsx','tenant_id'=>$selectedTenantId]) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Export Excel</a>
                <a href="{{ route('population.citizens.export', ['format'=>'csv','tenant_id'=>$selectedTenantId]) }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Export CSV</a>
            @endif
            @if(auth()->user()->hasPermission('population.manage') && (!auth()->user()->isSuperAdmin() || $selectedTenantId))
                <a href="{{ route('population.citizens.import') }}" class="rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Import</a>
                <button wire:click="create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"><span>+</span> Tambah Warga</button>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
        @if(auth()->user()->isSuperAdmin())<div class="w-full lg:max-w-md"><label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</label><select wire:model.live="selectedTenantId" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm"><option value="">Pilih tenant...</option>@foreach($tenants as $tenant)<option value="{{$tenant->id}}">{{$tenant->name}}{{ $tenant->code?' ('.$tenant->code.')':'' }}</option>@endforeach</select></div>@endif
        <div class="flex w-full flex-col gap-3 sm:flex-row lg:justify-end"><input wire:model.live.debounce.300ms="search" placeholder="Cari NIK atau nama..." class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm sm:max-w-sm"><select wire:model.live="perPage" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm"><option value="10">10 / halaman</option><option value="25">25 / halaman</option><option value="50">50 / halaman</option></select></div>
    </div></div>

    @if($selectedTenantId || auth()->user()->tenant_id)
        @if($showForm)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-base font-semibold text-slate-900">{{$editingId?'Edit Data Warga':'Tambah Data Warga'}}</h2><p class="mt-1 text-sm text-slate-500">Lengkapi identitas, data keluarga, dan dokumen kependudukan.</p></div>
                <div class="space-y-7 p-6">
                    <section><h3 class="text-sm font-semibold text-slate-900">Identitas</h3><div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach([['nik','NIK'],['nama_lengkap','Nama Lengkap'],['tempat_lahir','Tempat Lahir'],['tanggal_lahir','Tanggal Lahir']] as [$f,$l])<div><label class="text-sm font-medium text-slate-700">{{$l}}</label><input wire:model="{{$f}}" type="{{$f==='tanggal_lahir'?'date':'text'}}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($f)<p class="mt-1 text-xs text-red-600">{{$message}}</p>@enderror</div>@endforeach
                        <div><label class="text-sm font-medium text-slate-700">Jenis Kelamin</label><select wire:model="jenis_kelamin" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="">Pilih</option><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></div>
                        <div><label class="text-sm font-medium text-slate-700">Golongan Darah</label><select wire:model="golongan_darah" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="">Pilih</option>@foreach(['A','B','AB','O','unknown'] as $v)<option value="{{$v}}">{{$v}}</option>@endforeach</select></div>
                    </div></section>
                    <section class="border-t border-slate-100 pt-6"><h3 class="text-sm font-semibold text-slate-900">Data Keluarga</h3><div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">@foreach([['nama_ayah','Nama Ayah'],['nik_ayah','NIK Ayah'],['nama_ibu','Nama Ibu'],['nik_ibu','NIK Ibu'],['status_perkawinan','Status Perkawinan']] as [$f,$l])<div><label class="text-sm font-medium text-slate-700">{{$l}}</label><input wire:model="{{$f}}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($f)<p class="mt-1 text-xs text-red-600">{{$message}}</p>@enderror</div>@endforeach</div></section>
                    <section class="border-t border-slate-100 pt-6"><h3 class="text-sm font-semibold text-slate-900">Kewarganegaraan & Dokumen</h3><div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach([['kewarganegaraan','Kewarganegaraan'],['no_passport','No. Passport'],['no_kitap','No. KITAP']] as [$f,$l])<div><label class="text-sm font-medium text-slate-700">{{$l}}</label><input wire:model="{{$f}}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($f)<p class="mt-1 text-xs text-red-600">{{$message}}</p>@enderror</div>@endforeach</div></section>
                    <section class="border-t border-slate-100 pt-6"><h3 class="text-sm font-semibold text-slate-900">Pendidikan, Pekerjaan & Status</h3><div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach([['pendidikan','Pendidikan'],['pekerjaan','Pekerjaan'],['agama','Agama']] as [$f,$l])<div><label class="text-sm font-medium text-slate-700">{{$l}}</label><input wire:model="{{$f}}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($f)<p class="mt-1 text-xs text-red-600">{{$message}}</p>@enderror</div>@endforeach<div><label class="text-sm font-medium text-slate-700">Status Kependudukan</label><select wire:model="status_kependudukan" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="active">Aktif</option><option value="inactive">Tidak Aktif</option><option value="deceased">Meninggal</option><option value="moved">Pindah</option></select></div></div></section>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4"><button wire:click="resetForm" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button wire:click="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Simpan</button></div>
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-slate-50"><tr><th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th><th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIK</th><th class="hidden px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 md:table-cell">Tempat, Tanggal Lahir</th><th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th><th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($citizens as $c)<tr class="transition hover:bg-slate-50/70"><td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">{{$c->nama_lengkap}}</td><td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-slate-600">{{$c->nik}}</td><td class="hidden px-6 py-4 text-sm text-slate-600 md:table-cell">{{$c->tempat_lahir?:'-'}}, {{$c->tanggal_lahir?->format('d/m/Y')?:'-'}}</td><td class="px-6 py-4"><span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ucfirst($c->status_kependudukan)}}</span></td><td class="px-6 py-4 text-right"><a href="{{ route(auth()->user()->isSuperAdmin() ? 'population.admin.citizens.show' : 'population.citizens.show', $c) }}" class="mr-3 text-sm font-semibold text-slate-700 hover:text-slate-950">Detail</a>@if(auth()->user()->hasPermission('population.manage'))<button wire:click="edit('{{$c->id}}')" class="text-sm font-semibold text-slate-700 hover:text-slate-950">Edit</button>@endif</td></tr>
            @empty<tr><td colspan="5" class="px-6 py-14 text-center"><p class="text-sm font-medium text-slate-700">Belum ada data warga</p></td></tr>@endforelse
            </tbody></table></div><div class="border-t border-slate-100 px-6 py-4">{{$citizens->links()}}</div></div>
    @endif
</div>
