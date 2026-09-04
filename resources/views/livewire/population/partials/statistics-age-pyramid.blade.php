<section class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm">
    <div class="absolute -right-12 -top-12 h-32 w-32 rounded-full bg-indigo-500/5 blur-2xl"></div>
    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">▥</div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="font-semibold text-gray-900">Piramida Penduduk</h2>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">17 kelompok usia</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">Distribusi penduduk menurut umur dan jenis kelamin.</p>
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-2.5 py-1.5 font-semibold text-sky-700"><span class="h-2 w-2 rounded-full bg-sky-500"></span>{{ number_format($male) }} laki-laki</span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-pink-50 px-2.5 py-1.5 font-semibold text-pink-700"><span class="h-2 w-2 rounded-full bg-pink-500"></span>{{ number_format($female) }} perempuan</span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-2.5 py-1.5 font-semibold text-slate-600">{{ number_format($classifiedAge) }} terklasifikasi</span>
                    @if($unclassifiedAge > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1.5 font-semibold text-amber-700">{{ number_format($unclassifiedAge) }} tanpa tanggal lahir</span>
                    @endif
                </div>
            </div>
        </div>
        <button type="button" wire:click="openAgePyramid" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"><span>Lihat detail piramida</span><span aria-hidden="true">→</span></button>
    </div>
</section>