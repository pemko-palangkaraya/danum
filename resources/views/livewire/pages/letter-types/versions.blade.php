<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Master Jenis Surat</p>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $letterType->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $letterType->code }} · Riwayat versi template dan periode berlakunya.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('letter-types.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
            <button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Tambah Versi</button>
        </div>
    </div>

    <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm text-indigo-900">
        <strong>Aturan versi:</strong> versi yang sudah dibuat tidak diedit atau dihapus. Surat yang sudah menggunakan versi lama tetap menunjuk versi tersebut. DANUM memilih versi berdasarkan periode efektif.
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($versions as $version)
                @php
                    $now = now();
                    $isCurrent = $version->is_active && ($version->effective_from === null || $version->effective_from->lte($now)) && ($version->effective_until === null || $version->effective_until->gt($now));
                    $isScheduled = $version->effective_from !== null && $version->effective_from->gt($now);
                @endphp
                <div class="p-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">v{{ $version->version }}</span>
                                @if ($isCurrent)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Sedang berlaku</span>
                                @elseif ($isScheduled)
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Terjadwal</span>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Historis</span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm font-medium text-slate-900">{{ $version->change_note ?: 'Tidak ada catatan perubahan.' }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $version->effective_from?->format('d M Y H:i') ?? 'Tanpa tanggal mulai' }}
                                →
                                {{ $version->effective_until?->format('d M Y H:i') ?? 'sekarang' }}
                            </p>
                        </div>
                        <div class="text-xs text-slate-500 lg:text-right">
                            <p>Dibuat oleh</p>
                            <p class="mt-1 font-medium text-slate-700">{{ $version->creator?->name ?? 'Sistem' }}</p>
                            <p class="mt-1">{{ $version->created_at?->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-3 text-xs text-slate-600 sm:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 p-3"><span class="font-semibold text-slate-700">Template</span><br>{{ $version->template_path ? 'DOCX tersedia' : 'Body template' }}</div>
                        <div class="rounded-lg bg-slate-50 p-3"><span class="font-semibold text-slate-700">ID Versi</span><br><code>{{ $version->id }}</code></div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">Belum ada versi template.</div>
            @endforelse
        </div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-semibold text-slate-900">Tambah Versi Template</h2>
                    <p class="mt-1 text-sm text-slate-500">Upload DOCX baru. Sebelum disimpan, DANUM akan mencocokkan semua placeholder DOCX dengan variabel yang didefinisikan pada jenis surat.</p>
                </div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div><label class="text-sm font-semibold text-slate-800">Template DOCX</label><p class="mt-1 text-xs text-slate-500">Maksimal 10 MB. Pemeriksaan otomatis setelah file dipilih.</p></div>
                            @if ($templateCheckStatus === 'passed')
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">✓ XC PASS</span>
                            @elseif ($templateCheckStatus === 'failed')
                                <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">✕ XC GAGAL</span>
                            @endif
                        </div>
                        <input wire:model="template_file" type="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="mt-3 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                        @error('template_file')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                        <button type="button" wire:click="checkTemplate" wire:loading.attr="disabled" class="mt-3 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">Periksa Template</button>

                        @if ($templateCheckStatus !== '')
                            <div class="mt-4 space-y-3 rounded-xl border bg-white p-4 {{ $templateCheckStatus === 'passed' ? 'border-emerald-200' : 'border-rose-200' }}">
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Variabel pada DOCX</p><div class="mt-2 flex flex-wrap gap-1.5">@forelse ($templateFoundVariables as $variable)<span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700">{{ $variable }}</span>@empty<span class="text-xs text-slate-400">Tidak ditemukan</span>@endforelse</div></div>
                                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Variabel yang diharapkan</p><div class="mt-2 flex flex-wrap gap-1.5">@forelse ($declaredVariables as $variable)<span class="rounded-md bg-indigo-50 px-2 py-1 font-mono text-xs text-indigo-700">{{ $variable }}</span>@empty<span class="text-xs text-slate-400">Belum didefinisikan</span>@endforelse</div></div>
                                @if ($templateUnknownVariables)<div><p class="text-xs font-semibold text-rose-700">Ada di DOCX, tetapi belum terdaftar</p><div class="mt-2 flex flex-wrap gap-1.5">@foreach ($templateUnknownVariables as $variable)<span class="rounded-md bg-rose-50 px-2 py-1 font-mono text-xs text-rose-700">{{ $variable }}</span>@endforeach</div></div>@endif
                                @if ($templateMissingVariables)<div><p class="text-xs font-semibold text-amber-700">Terdaftar, tetapi tidak ditemukan di DOCX</p><div class="mt-2 flex flex-wrap gap-1.5">@foreach ($templateMissingVariables as $variable)<span class="rounded-md bg-amber-50 px-2 py-1 font-mono text-xs text-amber-700">{{ $variable }}</span>@endforeach</div></div>@endif
                                @if ($templateCheckStatus === 'passed')<p class="text-xs font-medium text-emerald-700">✓ Semua variabel sinkron. Versi boleh disimpan.</p>@endif
                            </div>
                        @endif
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Berlaku mulai</label><input wire:model="effective_from" type="datetime-local" class="form-control mt-1">@error('effective_from')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Berlaku sampai <span class="font-normal text-slate-400">(opsional)</span></label><input wire:model="effective_until" type="datetime-local" class="form-control mt-1">@error('effective_until')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="text-sm font-medium text-slate-700">Catatan perubahan</label><textarea wire:model="change_note" rows="4" maxlength="2000" placeholder="Contoh: Penyesuaian format nomor surat dan blok penandatangan." class="form-textarea mt-1"></textarea>@error('change_note')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button><button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Simpan Versi</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
