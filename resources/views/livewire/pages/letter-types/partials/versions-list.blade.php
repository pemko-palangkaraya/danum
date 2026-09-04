<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Riwayat Versi</h2>
            <p class="mt-1 text-xs text-slate-500">Tampilkan versi template per halaman.</p>
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <span class="whitespace-nowrap">Per halaman</span>
            <select wire:model.live="perPage" class="form-select w-24">
                <option value="5">5</option><option value="10">10</option><option value="25">25</option><option value="50">50</option>
            </select>
        </label>
    </div>
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
                            @if ($isCurrent)<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Sedang berlaku</span>
                            @elseif ($isScheduled)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Terjadwal</span>
                            @else<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">Historis</span>@endif
                        </div>
                        <p class="mt-3 text-sm font-medium text-slate-900">{{ $version->change_note ?: 'Tidak ada catatan perubahan.' }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $version->effective_from?->format('d M Y H:i') ?? 'Tanpa tanggal mulai' }} → {{ $version->effective_until?->format('d M Y H:i') ?? 'sekarang' }}</p>
                    </div>
                    <div class="text-xs text-slate-500 lg:text-right"><p>Dibuat oleh</p><p class="mt-1 font-medium text-slate-700">{{ $version->creator?->name ?? 'Sistem' }}</p><p class="mt-1">{{ $version->created_at?->format('d M Y H:i') }}</p></div>
                </div>
                <div class="mt-4 grid gap-3 text-xs text-slate-600 sm:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 p-3"><span class="font-semibold text-slate-700">Template</span><br>{{ $version->template_path ? 'DOCX tersedia' : 'Body template' }}</div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="font-semibold text-slate-700">Variabel</span><br>{{ count($version->variables ?? []) }} input snapshot</div>
                    <div class="rounded-lg bg-slate-50 p-3"><span class="font-semibold text-slate-700">ID Versi</span><br><code>{{ $version->id }}</code></div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center text-sm text-slate-500">Belum ada versi template.</div>
        @endforelse
    </div>
    @if ($versions->hasPages())<div class="border-t border-slate-100 p-4">{{ $versions->onEachSide(1)->links() }}</div>@endif
</div>
