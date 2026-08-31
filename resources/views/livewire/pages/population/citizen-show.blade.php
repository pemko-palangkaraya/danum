<?php

declare(strict_types=1);

use App\Models\Citizen;
use App\Models\CitizenAddress;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public Citizen $citizen;
    public string $alamat = '';
    public string $rt = '';
    public string $rw = '';
    public string $kelurahan = '';
    public string $kecamatan = '';
    public string $kabupaten_kota = '';
    public string $provinsi = '';
    public string $kode_pos = '';
    public string $jenis_alamat = 'domisili';
    public string $berlaku_mulai = '';

    public function mount(Citizen $citizen): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->tenant_id === $citizen->tenant_id, 404);
        $this->citizen = $citizen->load([
            'familyMemberships.family.headCitizen',
            'familyMemberships.family.activeMembers.citizen',
            'addresses' => fn ($query) => $query->latest('berlaku_mulai')->latest('created_at'),
            'populationEvents' => fn ($query) => $query->latest('event_date'),
        ]);
    }

    public function saveAddress(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);
        $data = Validator::make($this->only([
            'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos', 'jenis_alamat', 'berlaku_mulai',
        ]), [
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

        $data['citizen_id'] = $this->citizen->id;
        CitizenAddress::create($data);
        $this->citizen->load(['addresses' => fn ($query) => $query->latest('berlaku_mulai')->latest('created_at')]);
        $this->reset(['alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota', 'provinsi', 'kode_pos', 'berlaku_mulai']);
        $this->jenis_alamat = 'domisili';
        $this->dispatch('toast', type: 'success', message: 'Alamat warga berhasil ditambahkan.');
    }

    public function with(): array
    {
        $activeMembership = $this->citizen->familyMemberships->first(fn ($member) => $member->status === 'active');
        return ['activeMembership' => $activeMembership];
    }
};
?>
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ url()->previous() }}" class="text-sm font-semibold text-slate-500 hover:text-slate-900">← Kembali</a>
            <p class="mt-4 text-sm font-medium text-slate-500">Kependudukan</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $citizen->nama_lengkap }}</h1>
            <p class="mt-1 font-mono text-sm text-slate-500">NIK {{ $citizen->nik }}</p>
        </div>
        @if(auth()->user()->hasPermission('population.manage'))
            <a href="{{ url()->previous() }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Edit di Data Warga</a>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-semibold text-slate-900">Identitas Penduduk</h2></div>
            <dl class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    ['NIK', $citizen->nik], ['Nama Lengkap', $citizen->nama_lengkap], ['Tempat Lahir', $citizen->tempat_lahir ?: '-'],
                    ['Tanggal Lahir', $citizen->tanggal_lahir?->format('d/m/Y') ?: '-'], ['Jenis Kelamin', $citizen->jenis_kelamin === 'male' ? 'Laki-laki' : ($citizen->jenis_kelamin === 'female' ? 'Perempuan' : '-')],
                    ['Golongan Darah', $citizen->golongan_darah ?: '-'], ['Agama', $citizen->agama ?: '-'], ['Status Perkawinan', $citizen->status_perkawinan ?: '-'],
                    ['Pendidikan', $citizen->pendidikan ?: '-'], ['Pekerjaan', $citizen->pekerjaan ?: '-'], ['Kewarganegaraan', $citizen->kewarganegaraan ?: '-'],
                    ['Status Kependudukan', ucfirst($citizen->status_kependudukan)],
                ] as [$label, $value])
                    <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ $value }}</dd></div>
                @endforeach
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-semibold text-slate-900">Keluarga</h2></div>
            <div class="p-6">
                @if($activeMembership?->family)
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">No. KK</p>
                    <p class="mt-1 font-mono text-lg font-semibold text-slate-900">{{ $activeMembership->family->no_kk }}</p>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Hubungan</p>
                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $activeMembership->hubungan_dalam_keluarga }}</p>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Kepala Keluarga</p>
                    <p class="mt-1 text-sm font-medium text-slate-800">{{ $activeMembership->family->headCitizen?->nama_lengkap ?: '-' }}</p>
                    <a href="{{ route(auth()->user()->isSuperAdmin() ? 'population.admin.families.index' : 'population.families.index') }}" class="mt-5 inline-block text-sm font-semibold text-slate-700 hover:text-slate-950">Buka Kartu Keluarga →</a>
                @else
                    <p class="text-sm text-slate-500">Warga belum memiliki keanggotaan KK aktif.</p>
                @endif
            </div>
        </section>
    </div>

    @if(auth()->user()->hasPermission('population.manage'))
    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-semibold text-slate-900">Tambah Riwayat Alamat</h2><p class="mt-1 text-sm text-slate-500">Alamat disimpan sebagai riwayat sehingga perubahan domisili tidak menghapus data sebelumnya.</p></div>
        <div class="grid gap-5 p-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-4"><label class="text-sm font-medium text-slate-700">Alamat</label><textarea wire:model="alamat" rows="2" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></textarea></div>
            @foreach([['rt','RT'],['rw','RW'],['kelurahan','Kelurahan'],['kecamatan','Kecamatan'],['kabupaten_kota','Kabupaten/Kota'],['provinsi','Provinsi'],['kode_pos','Kode Pos'],['berlaku_mulai','Berlaku Mulai']] as [$field,$label])
                <div><label class="text-sm font-medium text-slate-700">{{ $label }}</label><input wire:model="{{ $field }}" type="{{ $field === 'berlaku_mulai' ? 'date' : 'text' }}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($field)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            @endforeach
            <div><label class="text-sm font-medium text-slate-700">Jenis Alamat</label><select wire:model="jenis_alamat" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><option value="domisili">Domisili</option><option value="ktp">KTP</option><option value="asal">Asal</option><option value="lainnya">Lainnya</option></select></div>
        </div>
        <div class="flex justify-end border-t border-slate-100 bg-slate-50/60 px-6 py-4"><button wire:click="saveAddress" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Simpan Alamat</button></div>
    </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-semibold text-slate-900">Riwayat Alamat</h2></div>
        <div class="divide-y divide-slate-100">
            @forelse($citizen->addresses as $address)
                <div class="px-6 py-5"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-semibold text-slate-900">{{ ucfirst($address->jenis_alamat) }}</p><p class="mt-1 text-sm text-slate-600">{{ $address->alamat ?: '-' }}{{ $address->rt || $address->rw ? ' • RT '.$address->rt.'/RW '.$address->rw : '' }}</p><p class="mt-1 text-xs text-slate-500">{{ collect([$address->kelurahan, $address->kecamatan, $address->kabupaten_kota, $address->provinsi, $address->kode_pos])->filter()->join(', ') }}</p></div><span class="text-xs text-slate-500">Mulai {{ $address->berlaku_mulai?->format('d/m/Y') ?: '-' }}</span></div></div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-500">Belum ada riwayat alamat.</div>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5"><h2 class="font-semibold text-slate-900">Riwayat Peristiwa Kependudukan</h2></div>
        <div class="divide-y divide-slate-100">
            @forelse($citizen->populationEvents as $event)
                <div class="flex flex-col gap-2 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm font-semibold text-slate-900">{{ $event->event_type }}</p><p class="mt-1 text-sm text-slate-500">{{ $event->notes ?: 'Tidak ada catatan.' }}</p></div><div class="text-sm text-slate-500">{{ $event->event_date?->format('d/m/Y') }}</div></div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-500">Belum ada peristiwa kependudukan.</div>
            @endforelse
        </div>
    </section>
</div>
