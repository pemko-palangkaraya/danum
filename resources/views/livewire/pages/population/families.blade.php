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
    public int $perPage = 10;
    public ?string $selectedTenantId = null;
    public ?string $editingId = null;
    public bool $showForm = false;
    public string $no_kk = '', $head_citizen_id = '', $alamat = '', $rt = '', $rw = '', $kelurahan = '', $kecamatan = '', $kabupaten_kota = '', $provinsi = '', $kode_pos = '', $status = 'active';
    public ?string $detailId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);
        if (! auth()->user()->isSuperAdmin()) {
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
        return Family::query()->where('tenant_id', $this->tenantId())
            ->with(['headCitizen', 'activeMembers.citizen'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q->where('no_kk', 'ilike', '%'.$this->search.'%')->orWhereHas('headCitizen', fn ($q) => $q->where('nama_lengkap', 'ilike', '%'.$this->search.'%'))))
            ->orderBy('no_kk');
    }

    public function updatedSelectedTenantId(): void { abort_unless(auth()->user()->isSuperAdmin(), 403); $this->resetPage(); $this->resetForm(); $this->detailId = null; }
    public function updatedSearch(): void { $this->resetPage(); }

    public function create(): void { abort_unless(auth()->user()->hasPermission('population.manage'), 403); $this->resetForm(); $this->showForm = true; }

    public function edit(string $id): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $f = $this->query()->findOrFail($id);
        $this->editingId = $f->id;
        foreach (['no_kk','alamat','rt','rw','kelurahan','kecamatan','kabupaten_kota','provinsi','kode_pos','status'] as $field) $this->{$field} = (string) ($f->{$field} ?? '');
        $this->head_citizen_id = (string) ($f->head_citizen_id ?? '');
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $tenantId = $this->tenantId();
        $data = Validator::make($this->only(['no_kk','head_citizen_id','alamat','rt','rw','kelurahan','kecamatan','kabupaten_kota','provinsi','kode_pos','status']), [
            'no_kk' => ['required','digits:16','unique:families,no_kk,'.($this->editingId ?? 'NULL').',id,tenant_id,'.$tenantId],
            'head_citizen_id' => ['nullable','uuid', 'exists:citizens,id'], 'alamat' => ['required','string','max:500'],
            'rt' => ['nullable','string','max:10'], 'rw' => ['nullable','string','max:10'], 'kelurahan' => ['nullable','string','max:100'],
            'kecamatan' => ['nullable','string','max:100'], 'kabupaten_kota' => ['nullable','string','max:100'], 'provinsi' => ['nullable','string','max:100'],
            'kode_pos' => ['nullable','string','max:10'], 'status' => ['required','string','max:30'],
        ])->validate();
        if (! empty($data['head_citizen_id'])) abort_unless(Citizen::whereKey($data['head_citizen_id'])->where('tenant_id', $tenantId)->exists(), 422);
        $data['tenant_id'] = $tenantId; $data['updated_by'] = auth()->id();
        if ($this->editingId) $this->query()->findOrFail($this->editingId)->update($data); else { $data['created_by'] = auth()->id(); Family::create($data); }
        $this->resetForm(); $this->dispatch('toast', type: 'success', message: 'Data KK berhasil disimpan.');
    }

    public function show(string $id): void { $this->detailId = $this->query()->findOrFail($id)->id; $this->showForm = false; }

    public function addMember(string $citizenId): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $family = $this->query()->findOrFail($this->detailId);
        $citizen = Citizen::where('tenant_id', $this->tenantId())->findOrFail($citizenId);
        abort_if(FamilyMember::where('citizen_id',$citizen->id)->where('status','active')->exists(), 422, 'Warga sudah memiliki KK aktif.');
        FamilyMember::create(['family_id'=>$family->id,'citizen_id'=>$citizen->id,'hubungan_dalam_keluarga'=>'family_member','urutan'=>$family->activeMembers()->count()+1,'tanggal_mulai'=>now()->toDateString(),'status'=>'active']);
    }

    public function removeMember(string $memberId): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $member = FamilyMember::whereHas('family', fn($q) => $q->where('tenant_id',$this->tenantId()))->findOrFail($memberId);
        $member->update(['status'=>'inactive','tanggal_selesai'=>now()->toDateString()]);
    }

    public function resetForm(): void { foreach(['no_kk','head_citizen_id','alamat','rt','rw','kelurahan','kecamatan','kabupaten_kota','provinsi','kode_pos'] as $f) $this->{$f}=''; $this->status='active'; $this->editingId=null; $this->showForm=false; $this->resetValidation(); }

    public function with(): array
    {
        $tenant = $this->selectedTenantId || auth()->user()->tenant_id;
        return ['families'=>$tenant ? $this->query()->paginate($this->perPage) : collect(), 'tenants'=>auth()->user()->isSuperAdmin()?Tenant::orderBy('name')->get(['id','name','code']):collect(), 'citizens'=> $tenant ? Citizen::where('tenant_id',$this->tenantId())->orderBy('nama_lengkap')->get(['id','nik','nama_lengkap']) : collect(), 'detail'=>$this->detailId ? $this->query()->find($this->detailId) : null];
    }
};
?>
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm text-slate-500">Kependudukan</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Kartu Keluarga</h1><p class="mt-1 text-sm text-slate-500">Data keluarga dan anggota keluarga.</p></div>@if(auth()->user()->hasPermission('population.manage') && (!auth()->user()->isSuperAdmin() || $selectedTenantId))<button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">+ Tambah KK</button>@endif</div>
    @if(auth()->user()->isSuperAdmin())<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><label class="text-sm font-medium">Pilih Tenant</label><select wire:model.live="selectedTenantId" class="mt-2 w-full max-w-xl rounded-xl border px-4 py-2.5"><option value="">Pilih tenant...</option>@foreach($tenants as $tenant)<option value="{{$tenant->id}}">{{$tenant->name}}{{$tenant->code?' ('.$tenant->code.')':''}}</option>@endforeach</select></div>@endif
    @if($selectedTenantId || auth()->user()->tenant_id)
        <input wire:model.live.debounce.300ms="search" placeholder="Cari No. KK atau kepala keluarga..." class="w-full max-w-md rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
        @if($showForm)<div class="rounded-2xl border bg-white p-6 shadow-sm"><h2 class="font-semibold">{{$editingId?'Edit KK':'Tambah KK'}}</h2><div class="mt-5 grid gap-4 sm:grid-cols-2"><div><label class="text-sm font-medium">No. KK</label><input wire:model="no_kk" maxlength="16" class="mt-2 w-full rounded-xl border px-3 py-2.5">@error('no_kk')<p class="text-xs text-red-600">{{$message}}</p>@enderror</div><div><label class="text-sm font-medium">Kepala Keluarga</label><select wire:model="head_citizen_id" class="mt-2 w-full rounded-xl border px-3 py-2.5"><option value="">Pilih warga</option>@foreach($citizens as $c)<option value="{{$c->id}}">{{$c->nama_lengkap}} — {{$c->nik}}</option>@endforeach</select></div><div class="sm:col-span-2"><label class="text-sm font-medium">Alamat</label><textarea wire:model="alamat" class="mt-2 w-full rounded-xl border px-3 py-2.5"></textarea></div>@foreach([['rt','RT'],['rw','RW'],['kelurahan','Kelurahan'],['kecamatan','Kecamatan'],['kabupaten_kota','Kabupaten/Kota'],['provinsi','Provinsi'],['kode_pos','Kode Pos']] as [$f,$l])<div><label class="text-sm font-medium">{{$l}}</label><input wire:model="{{$f}}" class="mt-2 w-full rounded-xl border px-3 py-2.5"></div>@endforeach</div><div class="mt-5 flex justify-end gap-3"><button wire:click="resetForm" class="rounded-xl border px-4 py-2.5">Batal</button><button wire:click="save" class="rounded-xl bg-slate-900 px-5 py-2.5 font-semibold text-white">Simpan</button></div></div>@endif
        @if($detail)<div class="rounded-2xl border bg-white p-6 shadow-sm"><div class="flex justify-between"><div><h2 class="text-lg font-semibold">{{$detail->no_kk}}</h2><p class="text-sm text-slate-500">{{$detail->headCitizen?->nama_lengkap ?? 'Belum ada kepala keluarga'}}</p><p class="mt-2 text-sm">{{$detail->alamat}}, RT {{$detail->rt ?: '-'}} / RW {{$detail->rw ?: '-'}}</p></div><button wire:click="$set('detailId', null)" class="text-sm font-semibold">Tutup</button></div><div class="mt-5 overflow-x-auto"><table class="min-w-full"><thead><tr class="border-b text-left text-xs uppercase text-slate-500"><th class="px-3 py-3">Nama</th><th class="px-3 py-3">NIK</th><th class="px-3 py-3">Hubungan</th><th class="px-3 py-3">Aksi</th></tr></thead><tbody>@forelse($detail->activeMembers as $m)<tr class="border-b"><td class="px-3 py-3">{{$m->citizen?->nama_lengkap}}</td><td class="px-3 py-3">{{$m->citizen?->nik}}</td><td class="px-3 py-3">{{$m->hubungan_dalam_keluarga}}</td><td class="px-3 py-3">@if(auth()->user()->hasPermission('population.manage'))<button wire:click="removeMember('{{$m->id}}')" class="text-sm font-semibold text-red-600">Keluarkan</button>@endif</td></tr>@empty<tr><td colspan="4" class="px-3 py-6 text-center text-sm text-slate-500">Belum ada anggota aktif.</td></tr>@endforelse</tbody></table></div><div class="mt-5 flex flex-wrap gap-2">@if(auth()->user()->hasPermission('population.manage'))@foreach($citizens as $c)<button wire:click="addMember('{{$c->id}}')" class="rounded-lg border px-3 py-2 text-xs">+ {{$c->nama_lengkap}}</button>@endforeach@endif</div></div>@endif
        <div class="overflow-hidden rounded-2xl border bg-white shadow-sm"><div class="overflow-x-auto"><table class="min-w-full divide-y"><thead class="bg-slate-50"><tr><th class="px-6 py-3 text-left text-xs uppercase text-slate-500">No. KK</th><th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Kepala Keluarga</th><th class="px-6 py-3 text-left text-xs uppercase text-slate-500">Alamat</th><th class="px-6 py-3 text-center text-xs uppercase text-slate-500">Anggota</th><th class="px-6 py-3 text-right text-xs uppercase text-slate-500">Aksi</th></tr></thead><tbody class="divide-y">@forelse($families as $f)<tr><td class="px-6 py-4 font-medium">{{$f->no_kk}}</td><td class="px-6 py-4">{{$f->headCitizen?->nama_lengkap ?? '-'}}</td><td class="px-6 py-4 text-sm">{{$f->alamat}}</td><td class="px-6 py-4 text-center">{{$f->activeMembers->count()}}</td><td class="px-6 py-4 text-right"><button wire:click="show('{{$f->id}}')" class="mr-3 text-sm font-semibold">Detail</button>@if(auth()->user()->hasPermission('population.manage'))<button wire:click="edit('{{$f->id}}')" class="text-sm font-semibold">Edit</button>@endif</td></tr>@empty<tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada data KK.</td></tr>@endforelse</tbody></table></div><div class="border-t px-6 py-3">{{$families->links()}}</div></div>
    @elseif(auth()->user()->isSuperAdmin())<div class="rounded-2xl border border-dashed px-6 py-12 text-center text-sm text-slate-500">Pilih tenant terlebih dahulu.</div>@endif
</div>
