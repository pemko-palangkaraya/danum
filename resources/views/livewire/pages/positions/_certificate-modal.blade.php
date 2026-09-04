@if($showCertificate)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showCertificate', false)">
        <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Sertifikat TTE</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $certificatePositionName }} · {{ $certificateHolderName }}</p>
                </div>
                <button type="button" wire:click="$set('showCertificate', false)" class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-100">✕</button>
            </div>

            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                @if($certificate && $certificate->isUsable())
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-semibold text-emerald-700">Sertifikat aktif</div>
                            <div class="mt-1 text-xs text-slate-500">Berlaku {{ $certificate->valid_from?->format('d M Y') }} — {{ $certificate->valid_until?->format('d M Y') }}</div>
                            <div class="mt-2 break-all font-mono text-[11px] text-slate-500">SHA-256: {{ $certificate->fingerprint_sha256 }}</div>
                        </div>
                        <button type="button" wire:click="downloadCertificate('{{ $certificatePositionId }}')" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Download Public Cert</button>
                    </div>
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <p class="text-xs text-amber-700">Generate ulang akan menonaktifkan sertifikat aktif sebelumnya.</p>
                        <button wire:click="generateCertificate" type="button" wire:confirm="Generate sertifikat baru untuk pejabat ini? Sertifikat aktif sebelumnya akan dinonaktifkan." class="mt-3 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Generate Ulang</button>
                    </div>
                @else
                    <div class="text-sm font-semibold text-amber-700">Belum ada sertifikat aktif</div>
                    <p class="mt-1 text-xs text-slate-500">DANUM akan membuat pasangan kunci RSA dan sertifikat publik self-signed. Private key disimpan terenkripsi dan tidak pernah ditampilkan.</p>
                    <button wire:click="generateCertificate" type="button" class="mt-4 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Generate Sertifikat</button>
                @endif
                @error('certificatePositionId')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" wire:click="$set('showCertificate', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Tutup</button>
            </div>
        </div>
    </div>
@endif
