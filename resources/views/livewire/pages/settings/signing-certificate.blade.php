<?php

declare(strict_types=1);

use App\Models\SignerCertificate;
use App\Models\Position;
use App\Services\SignerCertificateService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $positionId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('outgoing-letters.issue'), 403);
    }

    public function generate(SignerCertificateService $service): void
    {
        $this->validate([
            'positionId' => ['required', 'uuid'],
        ]);

        $user = auth()->user();

        $position = Position::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereKey($this->positionId)
            ->where('can_sign', true)
            ->whereHas('holders', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->where('started_at', '<=', now()))
            ->firstOrFail();

        $holder = $position->holders()
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->where('started_at', '<=', now())
            ->latest('started_at')
            ->firstOrFail();

        $service->generate($position, $holder, $user);

        $this->positionId = '';
        $this->dispatch('toast', type: 'success', message: 'Sertifikat TTE berhasil dibuat. Sertifikat sebelumnya pada jabatan ini dinonaktifkan.');
    }

    public function render(): mixed
    {
        $user = auth()->user();

        $positions = Position::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('can_sign', true)
            ->whereHas('holders', fn ($query) => $query
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->where('started_at', '<=', now()))
            ->with(['signerCertificates' => fn ($query) => $query->where('is_active', true)->latest('created_at')])
            ->orderBy('name')
            ->get();

        $certificates = SignerCertificate::query()
            ->where('user_id', $user->id)
            ->with('position')
            ->latest('created_at')
            ->get();

        return view('livewire.pages.settings.signing-certificate', compact('positions', 'certificates'));
    }
};
?>

<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <p class="text-sm text-slate-500">Pengaturan Keamanan</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Sertifikat TTE</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola sertifikat tanda tangan elektronik yang digunakan untuk menerbitkan surat.</p>
    </div>

    @if($positions->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
            Belum ada jabatan aktif yang dapat digunakan untuk tanda tangan. Pastikan Anda menjadi pemegang jabatan dengan kemampuan <strong>can sign</strong>.
        </div>
    @else
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-900">Buat / Perbarui Sertifikat</h2>
            <p class="mt-1 text-sm text-slate-500">Pembuatan sertifikat baru akan menonaktifkan sertifikat aktif sebelumnya untuk jabatan tersebut.</p>

            <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                <select wire:model="positionId" class="form-select flex-1">
                    <option value="">Pilih jabatan penandatangan</option>
                    @foreach($positions as $position)
                        <option value="{{ $position->id }}">{{ $position->name }}{{ $position->signerCertificates->isNotEmpty() ? ' · Sertifikat aktif' : ' · Belum ada sertifikat' }}</option>
                    @endforeach
                </select>
                <button
                    type="button"
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    Buat Sertifikat
                </button>
            </div>
            @error('positionId')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-900">Sertifikat Saya</h2>
            <p class="mt-1 text-xs text-slate-500">Private key disimpan terenkripsi dan tidak ditampilkan pada halaman ini.</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($certificates as $certificate)
                <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="font-semibold text-slate-900">{{ $certificate->position?->name ?? '-' }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            Serial {{ $certificate->serial_number }} · Berlaku {{ $certificate->valid_from?->format('d M Y') }} – {{ $certificate->valid_until?->format('d M Y') }}
                        </div>
                        <div class="mt-1 truncate font-mono text-[11px] text-slate-400">SHA-256 {{ $certificate->fingerprint_sha256 }}</div>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $certificate->isUsable() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                        {{ $certificate->is_usable() ? 'Aktif' : 'Tidak aktif' }}
                    </span>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">Belum ada sertifikat TTE.</div>
            @endforelse
        </div>
    </div>
</div>