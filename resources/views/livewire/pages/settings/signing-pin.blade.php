<?php

declare(strict_types=1);

use App\Services\AuditLogService;
use App\Services\SignerPinService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $signingPin = '';
    public string $signingPinConfirmation = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('outgoing-letters.issue'), 403);
    }

    public function save(SignerPinService $pinService, AuditLogService $auditLog): void
    {
        $validated = Validator::make(
            [
                'signingPin' => $this->signingPin,
                'signingPinConfirmation' => $this->signingPinConfirmation,
            ],
            [
                'signingPin' => ['required', 'digits:6'],
                'signingPinConfirmation' => ['required', 'same:signingPin'],
            ],
            [
                'signingPin.required' => 'PIN wajib diisi.',
                'signingPin.digits' => 'PIN harus terdiri dari 6 digit.',
                'signingPinConfirmation.required' => 'Konfirmasi PIN wajib diisi.',
                'signingPinConfirmation.same' => 'Konfirmasi PIN tidak sama.',
            ],
        )->validate();

        $pinService->set(auth()->user(), $validated['signingPin']);

        $auditLog->record(
            action: 'signer_pin.updated',
            user: auth()->user(),
            auditable: auth()->user(),
            newValues: ['configured' => true],
            tenantId: auth()->user()->tenant_id,
        );

        $this->reset(['signingPin', 'signingPinConfirmation']);
        $this->dispatch('toast', type: 'success', message: 'PIN tanda tangan berhasil disimpan.');
    }

    public function render(): mixed
    {
        return view('livewire.pages.settings.signing-pin', [
            'configured' => app(SignerPinService::class)->hasPin(auth()->user()),
            'setAt' => auth()->user()->signing_pin_set_at,
        ]);
    }
};
?>

<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <p class="text-sm text-slate-500">Pengaturan Keamanan</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">PIN Tanda Tangan</h1>
        <p class="mt-1 text-sm text-slate-500">PIN digunakan sebagai faktor otorisasi ketika menerbitkan surat secara elektronik.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Status PIN</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $configured ? 'PIN sudah dikonfigurasi.' : 'PIN belum dikonfigurasi.' }}
                    @if ($setAt)
                        Terakhir diatur {{ $setAt->format('d M Y H:i') }}.
                    @endif
                </p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $configured ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                {{ $configured ? 'Aktif' : 'Belum diatur' }}
            </span>
        </div>

        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            PIN terdiri dari 6 digit dan berbeda dari password login. PIN tidak pernah ditampilkan kembali setelah disimpan.
        </div>

        <form wire:submit="save" class="mt-6 space-y-5">
            <div>
                <label class="text-sm font-medium text-slate-700">PIN baru</label>
                <input wire:model="signingPin" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" class="form-control mt-1 tracking-[0.35em]" placeholder="••••••">
                @error('signingPin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="text-sm font-medium text-slate-700">Konfirmasi PIN</label>
                <input wire:model="signingPinConfirmation" type="password" inputmode="numeric" maxlength="6" autocomplete="new-password" class="form-control mt-1 tracking-[0.35em]" placeholder="••••••">
                @error('signingPinConfirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <a href="{{ route('dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">
                    Simpan PIN
                </button>
            </div>
        </form>
    </div>
</div>