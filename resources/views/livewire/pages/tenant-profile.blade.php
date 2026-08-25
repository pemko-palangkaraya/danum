<?php

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $name = '';
    public string $province = '';
    public string $city = '';
    public string $district = '';
    public string $village = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $logo = '';
    public string $head_name = '';
    public string $head_title = '';
    public string $currentLetterhead = '';

    public function mount(): void
    {
        $tenant = request()->user()->tenant;
        abort_unless($tenant, 404);
        $this->authorize('viewProfile', $tenant);

        $this->name = $tenant->name ?? '';
        $this->province = $tenant->province ?? '';
        $this->city = $tenant->city ?? '';
        $this->district = $tenant->district ?? '';
        $this->village = $tenant->village ?? '';
        $this->address = $tenant->address ?? '';
        $this->phone = $tenant->phone ?? '';
        $this->email = $tenant->email ?? '';
        $this->logo = $tenant->logo ?? '';
        $this->head_name = $tenant->head_name ?? '';
        $this->head_title = $tenant->head_title ?? '';
        $this->currentLetterhead = $tenant->letterheadUrl() ?? '';
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Profil Organisasi</h1>
        <p class="mt-1 text-sm text-slate-500">Informasi organisasi yang digunakan pada surat keluar.</p>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        Profil organisasi hanya dapat diubah oleh Administrator. Jika ada perubahan data, silakan hubungi Administrator.
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-slate-900">Kop Surat</h2>
            <p class="mt-1 text-xs text-slate-500">Kop surat resmi yang digunakan pada PDF surat yang diterbitkan.</p>
        </div>
        <div class="p-5 sm:p-6">
            <div class="flex min-h-32 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                @if ($currentLetterhead)
                <img src="{{ $currentLetterhead }}" alt="Kop surat saat ini" class="max-h-32 max-w-full object-contain">
                @else
                <div class="text-center text-xs text-slate-400">Belum ada kop surat</div>
                @endif
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-slate-900">Identitas</h2>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            @foreach (['name'=>'Nama Organisasi','phone'=>'Telepon','email'=>'Email'] as $field => $label)
            <div class="{{ $field === 'name' ? 'sm:col-span-2' : '' }}">
                <label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                <input id="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ $this->{$field} }}" disabled class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700">
            </div>
            @endforeach
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-slate-900">Alamat</h2>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
            @foreach (['province'=>'Provinsi','city'=>'Kota/Kabupaten','district'=>'Kecamatan','village'=>'Kelurahan/Desa'] as $field => $label)
            <div>
                <label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                <input id="{{ $field }}" type="text" value="{{ $this->{$field} }}" disabled class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700">
            </div>
            @endforeach
            <div class="sm:col-span-2 lg:col-span-4">
                <label for="address" class="block text-sm font-medium text-slate-700">Alamat Lengkap</label>
                <textarea id="address" rows="3" disabled class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700">{{ $address }}</textarea>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="text-sm font-semibold text-slate-900">Pimpinan</h2>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
            <div>
                <label for="head_name" class="block text-sm font-medium text-slate-700">Nama Pimpinan</label>
                <input id="head_name" type="text" value="{{ $head_name }}" disabled class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700">
            </div>
            <div>
                <label for="head_title" class="block text-sm font-medium text-slate-700">Jabatan</label>
                <input id="head_title" type="text" value="{{ $head_title }}" disabled class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700">
            </div>
        </div>
    </div>
</div>