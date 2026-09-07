@if($showAgePyramid)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="age-pyramid-title">
        <button type="button" wire:click="closeAgePyramid" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" aria-label="Tutup detail piramida"></button>
        <div class="relative z-10 flex max-h-[96vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-100 px-4 py-3 sm:px-6 sm:py-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">▥</span><div class="min-w-0"><h2 id="age-pyramid-title" class="font-semibold text-gray-900">Piramida Penduduk</h2><p class="mt-0.5 text-xs text-gray-500">Distribusi penduduk menurut kelompok umur 5 tahunan dan jenis kelamin.</p></div></div>
                </div>
                <button type="button" wire:click="closeAgePyramid" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">×</button>
            </div>
            <div class="overflow-y-auto px-3 py-4 sm:px-6 sm:py-5">
                <div class="mb-4 flex flex-col gap-2 rounded-xl bg-slate-50 px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-4">
                    <div class="flex flex-wrap gap-3 text-xs font-medium text-gray-600"><span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Laki-laki <span class="text-gray-400">({{ number_format($male) }})</span></span><span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-pink-500"></span>Perempuan <span class="text-gray-400">({{ number_format($female) }})</span></span></div>
                    <span class="text-xs text-slate-500">{{ number_format($classifiedAge) }} penduduk terklasifikasi</span>
                </div>
                <div class="w-full overflow-x-auto pb-1">
                    <div class="mx-auto w-full min-w-[480px] max-w-5xl sm:min-w-[620px]">
                        <div class="mb-3 grid grid-cols-[minmax(0,1fr)_52px_minmax(0,1fr)] items-center gap-1 text-[10px] font-semibold uppercase tracking-wide sm:grid-cols-[1fr_64px_1fr] sm:gap-2 sm:text-xs"><div class="text-right text-sky-600">Laki-laki</div><div></div><div class="text-pink-600">Perempuan</div></div>
                        <div class="space-y-1">
                            @foreach($ageGroups as $label => $group)
                                <div class="grid grid-cols-[minmax(0,1fr)_52px_minmax(0,1fr)] items-center gap-1 sm:grid-cols-[1fr_64px_1fr] sm:gap-2"><div class="flex min-w-0 items-center justify-end gap-1 sm:gap-2"><span class="w-8 shrink-0 text-right text-[10px] font-medium text-slate-400 sm:w-12 sm:text-[11px]">{{ number_format($group['male']) }}</span><div class="flex h-4 min-w-0 w-full justify-end overflow-hidden rounded-l bg-sky-100 sm:h-5"><div class="h-full rounded-l bg-sky-500 transition-all" style="width: {{ $group['male_width'] }}%"></div></div></div><div class="text-center text-[10px] font-semibold text-slate-600 sm:text-[11px]">{{ $label }}</div><div class="flex min-w-0 items-center gap-1 sm:gap-2"><div class="h-4 min-w-0 w-full overflow-hidden rounded-r bg-pink-100 sm:h-5"><div class="h-full rounded-r bg-pink-500 transition-all" style="width: {{ $group['female_width'] }}%"></div></div><span class="w-8 shrink-0 text-[10px] font-medium text-slate-400 sm:w-12 sm:text-[11px]">{{ number_format($group['female']) }}</span></div></div>
                            @endforeach
                        </div>
                        <div class="mt-4 flex justify-center gap-4 text-center text-[10px] text-slate-400 sm:text-[11px]"><span>Skala relatif terhadap kelompok terbesar</span></div>
                    </div>
                </div>
                @if($unclassifiedAge > 0)
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3 text-xs text-amber-700 sm:px-4"><strong>{{ number_format($unclassifiedAge) }}</strong> penduduk belum dapat dimasukkan ke piramida karena tanggal lahir belum tersedia.</div>
                @endif
            </div>
            <div class="flex shrink-0 justify-end border-t border-slate-100 px-4 py-3 sm:px-6"><button type="button" wire:click="closeAgePyramid" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Tutup</button></div>
        </div>
    </div>
@endif