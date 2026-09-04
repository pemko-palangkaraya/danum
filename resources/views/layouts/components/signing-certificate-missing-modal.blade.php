@php
    $signingCertificateMissing = false;
    if (auth()->check() && auth()->user()->hasPermission('outgoing-letters.issue')) {
        $signingCertificateMissing = ! \App\Models\SignerCertificate::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->whereNull('revoked_at')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>', now())
            ->exists();
    }
@endphp

<div
    x-data="{ open: @js(request()->routeIs('outgoing-letters.index') && $signingCertificateMissing) }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/40 p-4"
    x-on:keydown.escape.window="open = false"
    x-on:click.self="open = false"
>
    <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl" x-show="open" x-transition>
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8 2.7 17a2 2 0 0 0 1.73 3h15.14a2 2 0 0 0 1.73-3L13.7 3.8a2 2 0 0 0-3.4 0Z" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">Sertifikat TTE Belum Siap</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Sertifikat TTE Anda belum dikonfigurasi atau sertifikat yang tersedia sudah kedaluwarsa. Buat atau perbarui sertifikat sebelum menerbitkan surat.</p>
                </div>
                <button type="button" x-on:click="open = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">✕</button>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-5">
            <button type="button" x-on:click="open = false" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Nanti</button>
            <a href="{{ route('settings.signing-certificate') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Atur Sertifikat TTE</a>
        </div>
    </div>
</div>
