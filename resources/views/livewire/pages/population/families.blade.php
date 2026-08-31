<?php

declare(strict_types=1);

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Tenant;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $headSearch = '';
    public string $memberSearch = '';
    public int $perPage = 10;
    public ?string $selectedTenantId = null;
    public ?string $editingId = null;
    public bool $showForm = false;
    public string $no_kk = '', $head_citizen_id = '', $alamat = '', $rt = '', $rw = '', $kelurahan = '', $kecamatan = '', $kabupaten_kota = '', $provinsi = '', $kode_pos = '', $status = 'active';
    public ?string $detailId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);
        if (!auth()->user()->isSuperAdmin()) {
            abort_unless(auth()->user()->tenant_id, 403);
            $this->selectedTenantId = auth()->user()->tenant_id;
        }
    }

    private function tenantId(): string
    {
        $id = auth()->user()->isSuperAdmin() ? $this->selectedTenantId : auth()->user()->tenant_id;
        abort_unless($id, 422);
        return (string) $id;
    }

    private function query()
    {
        return Family::query()
            ->where('tenant_id', $this->tenantId())
            ->with(['headCitizen', 'activeMembers.citizen'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) =>
                $q->where('no_kk', 'ilike', '%'.$this->search.'%')
                    ->orWhereHas('headCitizen', fn ($q) => $q->where('nama_lengkap', 'ilike', '%'.$this->search.'%'))
            ))
            ->orderBy('no_kk');
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
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $f = $this->query()->findOrFail($id);
        $this->editingId = $f->id;
        foreach (['no_kk', 'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos', 'status'] as $field) {
            $this->{$field} = (string) ($f->{$field} ?? '');
        }
        $this->head_citizen_id = (string) ($f->head_citizen_id ?? '');
        $this->headSearch = $f->headCitizen?->nama_lengkap ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $tenantId = $this->tenantId();
        $data = Validator::make(
            $this->only(['no_kk', 'head_citizen_id', 'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos', 'status']),
            [
                'no_kk' => ['required', 'digits:16', 'unique:families,no_kk,'.($this->editingId ?? 'NULL').',id,tenant_id,'.$tenantId],
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
            ]
        )->validate();

        if (!empty($data['head_citizen_id'])) {
            abort_unless(Citizen::whereKey($data['head_citizen_id'])->where('tenant_id', $tenantId)->exists(), 422);
        }

        $data['tenant_id'] = $tenantId;
        $data['updated_by'] = auth()->id();
        if ($this->editingId) {
            $this->query()->findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by'] = auth()->id();
            Family::create($data);
        }

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Data KK berhasil disimpan.');
    }

    public function show(string $id): void
    {
        $this->detailId = $this->query()->findOrFail($id)->id;
        $this->showForm = false;
        $this->memberSearch = '';
    }

    public function addMember(string $citizenId): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $family = $this->query()->findOrFail($this->detailId);
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
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $member = FamilyMember::whereHas('family', fn ($q) => $q->where('tenant_id', $this->tenantId()))->findOrFail($memberId);
        $member->update(['status' => 'inactive', 'tanggal_selesai' => now()->toDateString()]);
    }

    public function selectHead(string $citizenId): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
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
        foreach (['no_kk', 'head_citizen_id', 'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos'] as $f) {
            $this->{$f} = '';
        }
        $this->status = 'active';
        $this->editingId = null;
        $this->showForm = false;
        $this->headSearch = '';
        $this->memberSearch = '';
        $this->resetValidation();
    }

    public function with(): array
    {
        $tenant = $this->selectedTenantId || auth()->user()->tenant_id;
        $headCitizens = collect();
        $memberCandidates = collect();

        if ($tenant && $this->headSearch !== '') {
            $headCitizens = Citizen::query()
                ->where('tenant_id', $this->tenantId())
                ->where(fn ($q) =>
                    $q->where('nama_lengkap', 'ilike', '%'.$this->headSearch.'%')
                        ->orWhere('nik', 'ilike', '%'.$this->headSearch.'%')
                )
                ->orderBy('nama_lengkap')
                ->limit(15)
                ->get(['id', 'nik', 'nama_lengkap']);
        }

        if ($tenant && $this->detailId && $this->memberSearch !== '') {
            $activeMemberIds = FamilyMember::where('family_id', $this->detailId)
                ->where('status', 'active')
                ->pluck('citizen_id');

            $memberCandidates = Citizen::query()
                ->where('tenant_id', $this->tenantId())
                ->whereNotIn('id', $activeMemberIds)
                ->where(fn ($q) =>
                    $q->where('nama_lengkap', 'ilike', '%'.$this->memberSearch.'%')
                        ->orWhere('nik', 'ilike', '%'.$this->memberSearch.'%')
                )
                ->orderBy('nama_lengkap')
                ->limit(15)
                ->get(['id', 'nik', 'nama_lengkap']);
        }

        return [
            'families' => $tenant ? $this->query()->paginate($this->perPage) : collect(),
            'tenants' => auth()->user()->isSuperAdmin() ? Tenant::orderBy('name')->get(['id', 'name', 'code']) : collect(),
            'headCitizens' => $headCitizens,
            'memberCandidates' => $memberCandidates,
            'selectedHead' => $this->head_citizen_id && $tenant
                ? Citizen::where('tenant_id', $this->tenantId())->find($this->head_citizen_id)
                : null,
            'detail' => $this->detailId ? $this->query()->find($this->detailId) : null,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Kependudukan</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Kartu Keluarga</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola data kartu keluarga dan anggota keluarga.</p>
        </div>
        @if(auth()->user()->hasPermission('population.manage') && (!auth()->user()->isSuperAdmin() || $selectedTenantId))
            <button wire:click="create" class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                <span class="text-base leading-none">+</span> Tambah KK
            </button>
        @endif
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
            @if(auth()->user()->isSuperAdmin())
                <div class="w-full lg:max-w-md">
                    <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</label>
                    <select wire:model.live="selectedTenantId" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                        <option value="">Pilih tenant...</option>
                        @foreach($tenants as $tenant)
                            <option value="{{$tenant->id}}">{{$tenant->name}}{{ $tenant->code?' ('.$tenant->code.')':'' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex w-full flex-col gap-3 sm:flex-row lg:justify-end">
                <div class="relative w-full sm:max-w-sm">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Cari No. KK atau kepala keluarga..." class="w-full rounded-xl border border-slate-200 py-2.5 pl-9 pr-4 text-sm shadow-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                </div>
                <select wire:model.live="perPage" class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600 shadow-sm">
                    <option value="10">10 / halaman</option>
                    <option value="25">25 / halaman</option>
                    <option value="50">50 / halaman</option>
                </select>
            </div>
        </div>
    </div>

    @if($selectedTenantId || auth()->user()->tenant_id)
        @if($showForm)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-base font-semibold text-slate-900">{{$editingId?'Edit Kartu Keluarga':'Tambah Kartu Keluarga'}}</h2>
                    <p class="mt-1 text-sm text-slate-500">Lengkapi identitas dan alamat keluarga.</p>
                </div>
                <div class="p-6">
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="text-sm font-medium text-slate-700">No. KK</label>
                            <input wire:model="no_kk" maxlength="16" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                            @error('no_kk')<p class="mt-1 text-xs text-red-600">{{$message}}</p>@enderror
                        </div>
                        <div class="sm:col-span-1 lg:col-span-2">
                            <label class="text-sm font-medium text-slate-700">Kepala Keluarga</label>
                            <div class="relative mt-2">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7"></circle>
                                    <path d="m20 20-3.5-3.5"></path>
                                </svg>
                                <input wire:model.live.debounce.300ms="headSearch" placeholder="Ketik nama atau NIK..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 pl-10 pr-16 text-sm shadow-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                                @if($head_citizen_id)
                                    <button type="button" wire:click="resetHead" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 transition hover:text-slate-700">Hapus</button>
                                @endif
                            </div>
                            @if($selectedHead && $headSearch !== '')
                                <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-slate-800">{{$selectedHead->nama_lengkap}}</div>
                                        <div class="mt-0.5 font-mono text-xs text-slate-500">{{$selectedHead->nik}}</div>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-600">Terpilih</span>
                                </div>
                            @endif
                            @if($headSearch !== '' && $headCitizens->count())
                                <div class="mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                    @foreach($headCitizens as $c)
                                        <button type="button" wire:click="selectHead('{{$c->id}}')" class="flex w-full items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 text-left transition last:border-0 hover:bg-slate-50">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-semibold text-slate-800">{{$c->nama_lengkap}}</div>
                                                <div class="mt-0.5 font-mono text-xs text-slate-500">{{$c->nik}}</div>
                                            </div>
                                            <span class="shrink-0 text-xs font-semibold text-slate-400">Pilih</span>
                                        </button>
                                    @endforeach
                                </div>
                            @elseif($headSearch !== '' && !$headCitizens->count())
                                <p class="mt-2 text-xs text-slate-500">Warga tidak ditemukan.</p>
                            @endif
                            @error('head_citizen_id')<p class="mt-1 text-xs text-red-600">{{$message}}</p>@enderror
                        </div>
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label class="text-sm font-medium text-slate-700">Alamat</label>
                            <textarea wire:model="alamat" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></textarea>
                        </div>
                        @foreach([['rt','RT'],['rw','RW'],['kelurahan','Kelurahan'],['kecamatan','Kecamatan'],['kabupaten_kota','Kabupaten/Kota'],['provinsi','Provinsi'],['kode_pos','Kode Pos']] as [$f,$l])
                            <div>
                                <label class="text-sm font-medium text-slate-700">{{$l}}</label>
                                <input wire:model="{{$f}}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                    <button wire:click="resetForm" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button wire:click="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan</button>
                </div>
            </div>
        @endif

        @if($detail)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kartu Keluarga</p>
                        <h2 class="mt-1 font-mono text-lg font-semibold text-slate-900">{{$detail->no_kk}}</h2>
                        <p class="mt-1 text-sm text-slate-500">Kepala keluarga: {{$detail->headCitizen?->nama_lengkap??'Belum ditentukan'}}</p>
                        <p class="mt-2 text-sm text-slate-600">{{$detail->alamat}}, RT {{$detail->rt?:'-'}} / RW {{$detail->rw?:'-'}}</p>
                    </div>
                    <button wire:click="$set('detailId', null)" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIK</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Hubungan</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($detail->activeMembers as $m)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-6 py-3.5 text-sm font-medium">{{$m->citizen?->nama_lengkap}}</td>
                                    <td class="px-6 py-3.5 font-mono text-sm text-slate-600">{{$m->citizen?->nik}}</td>
                                    <td class="px-6 py-3.5 text-sm text-slate-600">{{$m->hubungan_dalam_keluarga}}</td>
                                    <td class="px-6 py-3.5 text-right">
                                        @if(auth()->user()->hasPermission('population.manage'))
                                            <button wire:click="removeMember('{{$m->id}}')" class="text-sm font-semibold text-red-600 hover:text-red-700">Keluarkan</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada anggota aktif.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(auth()->user()->hasPermission('population.manage'))
                    <div class="border-t border-slate-100 bg-white px-6 py-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-slate-800">Tambahkan Anggota</p>
                                <p class="mt-0.5 text-xs text-slate-500">Cari warga berdasarkan nama atau NIK.</p>
                            </div>
                            @if($memberSearch !== '')
                                <span class="text-xs text-slate-400">{{$memberCandidates->count()}} hasil</span>
                            @endif
                        </div>

                        <div class="relative mt-3">
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3.5-3.5"></path>
                            </svg>
                            <input wire:model.live.debounce.300ms="memberSearch" placeholder="Cari nama atau NIK..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-10 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                            @if($memberSearch !== '')
                                <button type="button" wire:click="$set('memberSearch', '')" aria-label="Bersihkan pencarian" class="absolute right-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M6 6l12 12M18 6 6 18"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        @if($memberSearch !== '')
                            <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                @forelse($memberCandidates as $c)
                                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-800">{{$c->nama_lengkap}}</div>
                                            <div class="mt-0.5 font-mono text-xs text-slate-500">{{$c->nik}}</div>
                                        </div>
                                        <button type="button" wire:click="addMember('{{$c->id}}')" class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M12 5v14M5 12h14"></path>
                                            </svg>
                                            Tambahkan
                                        </button>
                                    </div>
                                @empty
                                    <div class="px-4 py-7 text-center">
                                        <p class="text-sm font-medium text-slate-700">Warga tidak ditemukan</p>
                                        <p class="mt-1 text-xs text-slate-500">Coba gunakan nama lengkap atau NIK yang berbeda.</p>
                                    </div>
                                @endforelse
                            </div>
                        @else
                            <p class="mt-2 text-xs text-slate-400">Ketik nama atau NIK untuk mencari warga yang akan ditambahkan.</p>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No. KK</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kepala Keluarga</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</th>
                            <th class="px-6 py-3.5 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Anggota</th>
                            <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($families as $f)
                            <tr class="transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-6 py-4 font-mono text-sm font-semibold text-slate-900">{{$f->no_kk}}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-800">{{$f->headCitizen?->nama_lengkap??'-'}}</td>
                                <td class="max-w-md px-6 py-4 text-sm text-slate-600">{{$f->alamat}}</td>
                                <td class="px-6 py-4 text-center"><span class="inline-flex min-w-8 justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{$f->activeMembers->count()}}</span></td>
                                <td class="px-6 py-4 text-right">
                                    <button wire:click="show('{{$f->id}}')" class="mr-3 text-sm font-semibold text-slate-700 hover:text-slate-950">Detail</button>
                                    @if(auth()->user()->hasPermission('population.manage'))
                                        <button wire:click="edit('{{$f->id}}')" class="text-sm font-semibold text-slate-700 hover:text-slate-950">Edit</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center">
                                    <p class="text-sm font-medium text-slate-700">Belum ada data kartu keluarga</p>
                                    <p class="mt-1 text-sm text-slate-500">Data KK akan muncul di sini setelah ditambahkan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-500">Menampilkan {{$families->firstItem()?:0}}–{{$families->lastItem()?:0}} dari {{$families->total()}} KK</p>
                {{$families->links()}}
            </div>
        </div>
    @elseif(auth()->user()->isSuperAdmin())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
            <p class="text-sm font-medium text-slate-700">Pilih tenant terlebih dahulu</p>
            <p class="mt-1 text-sm text-slate-500">Pilih tenant pada filter di atas untuk melihat data KK.</p>
        </div>
    @endif
</div>