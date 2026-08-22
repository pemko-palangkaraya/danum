<?php

use App\Models\Tenant;
use App\Services\TenantProfileService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

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
    public $letterhead = null;
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

    public function save(TenantProfileService $profileService): void
    {
        $tenant = request()->user()->tenant;
        abort_unless($tenant, 404);
        $this->authorize('updateProfile', $tenant);

        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'logo' => ['nullable', 'string', 'max:255'],
            'head_name' => ['nullable', 'string', 'max:150'],
            'head_title' => ['nullable', 'string', 'max:100'],
            'letterhead' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        $letterheadPath = $tenant->letterhead_path;
        if ($this->letterhead) {
            $newPath = $this->letterhead->store('letterheads', 'public');
            if ($letterheadPath) {
                Storage::disk('public')->delete($letterheadPath);
            }
            $letterheadPath = $newPath;
        }

        $profileService->update($tenant, [
            'name' => $this->name,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'village' => $this->village,
            'address' => $this->address ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'logo' => $this->logo ?: null,
            'letterhead_path' => $letterheadPath,
            'head_name' => $this->head_name ?: null,
            'head_title' => $this->head_title ?: null,
        ]);

        $this->letterhead = null;
        $this->currentLetterhead = $tenant->refresh()->letterheadUrl() ?? '';
        session()->flash('profile_saved', 'Profil organisasi dan kop surat berhasil diperbarui.');
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Profil Organisasi</h1>
        <p class="mt-1 text-sm text-slate-500">Atur identitas yang tampil pada surat keluar, termasuk kop surat resmi.</p>
    </div>

    @if (session('profile_saved'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('profile_saved') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-slate-900">Kop Surat</h2>
                <p class="mt-1 text-xs text-slate-500">Upload gambar kop resmi. File ini akan dipakai pada PDF surat yang diterbitkan.</p>
            </div>
            <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[220px_minmax(0,1fr)]">
                <div class="flex min-h-32 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3">
                    @if ($letterhead)
                        <img src="{{ $letterhead->temporaryUrl() }}" alt="Preview kop surat" class="max-h-28 max-w-full object-contain">
                    @elseif ($currentLetterhead)
                        <img src="{{ $currentLetterhead }}" alt="Kop surat saat ini" class="max-h-28 max-w-full object-contain">
                    @else
                        <div class="text-center text-xs text-slate-400">Belum ada kop surat</div>
                    @endif
                </div>
                <div>
                    <label for="letterhead" class="block text-sm font-medium text-slate-700">Gambar Kop Surat</label>
                    <input id="letterhead" type="file" wire:model="letterhead" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <div wire:loading wire:target="letterhead" class="mt-2 text-xs text-slate-500">Mengunggah sementara...</div>
                    @error('letterhead')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    <p class="mt-2 text-xs leading-5 text-slate-500">PNG/JPG/WebP, maksimal 4 MB. Gunakan gambar kop dengan resolusi tinggi dan latar transparan/putih.</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Identitas</h2></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                @foreach (['name'=>'Nama Organisasi','phone'=>'Telepon','email'=>'Email'] as $field => $label)
                    <div class="{{ $field === 'name' ? 'sm:col-span-2' : '' }}">
                        <label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
                        <input id="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" wire:model="{{ $field }}" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">
                        @error($field)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Alamat</h2></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
                @foreach (['province'=>'Provinsi','city'=>'Kota/Kabupaten','district'=>'Kecamatan','village'=>'Kelurahan/Desa'] as $field => $label)
                    <div><label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label><input id="{{ $field }}" type="text" wire:model="{{ $field }}" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error($field)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @endforeach
                <div class="sm:col-span-2 lg:col-span-4"><label for="address" class="block text-sm font-medium text-slate-700">Alamat Lengkap</label><textarea id="address" wire:model="address" rows="3" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100"></textarea>@error('address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Pimpinan</h2></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div><label for="head_name" class="block text-sm font-medium text-slate-700">Nama Pimpinan</label><input id="head_name" type="text" wire:model="head_name" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></div>
                <div><label for="head_title" class="block text-sm font-medium text-slate-700">Jabatan</label><input id="head_title" type="text" wire:model="head_title" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></div>
            </div>
        </div>

        <div class="flex justify-end"><button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">Simpan Profil</button></div>
    </form>
</div>
