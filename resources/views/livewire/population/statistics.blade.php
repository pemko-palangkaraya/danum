<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">⌁</span><h1 class="text-xl font-bold tracking-tight text-gray-900">Statistik Kependudukan</h1></div>
            <p class="mt-1 text-sm text-gray-500">Ringkasan demografi dan komposisi penduduk.</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
            <select wire:model.live="selectedTenantId" class="rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"><option value="">Pilih tenant</option>@foreach(\App\Models\Tenant::query()->orderBy('name')->get() as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="group relative overflow-hidden rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"><div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-blue-500/10 blur-2xl"></div><div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Penduduk</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalCitizens) }}</p><p class="mt-1 text-xs text-gray-500">Warga terdata</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">👥</span></div></div>
        <div class="group relative overflow-hidden rounded-2xl border border-violet-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"><div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-violet-500/10 blur-2xl"></div><div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total KK</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalFamilies) }}</p><p class="mt-1 text-xs text-gray-500">Kartu keluarga</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">⌂</span></div></div>
        <div class="group relative overflow-hidden rounded-2xl border border-sky-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"><div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-sky-500/10 blur-2xl"></div><div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Laki-laki</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($male) }}</p><p class="mt-1 text-xs text-sky-600">{{ $totalCitizens ? number_format(($male / $totalCitizens) * 100, 1) : 0 }}% dari penduduk</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-600">♂</span></div></div>
        <div class="group relative overflow-hidden rounded-2xl border border-pink-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg"><div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-pink-500/10 blur-2xl"></div><div class="relative flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Perempuan</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($female) }}</p><p class="mt-1 text-xs text-pink-600">{{ $totalCitizens ? number_format(($female / $totalCitizens) * 100, 1) : 0 }}% dari penduduk</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl bg-pink-50 text-pink-600">♀</span></div></div>
    </div>

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
            <button type="button" wire:click="openAgePyramid" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <span>Lihat detail piramida</span>
                <span aria-hidden="true">→</span>
            </button>
        </div>
    </section>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <section class="group rounded-2xl border border-amber-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Balita</p><p class="mt-2 text-3xl font-extrabold text-gray-900">{{ number_format($toddlers) }}</p><p class="mt-1 text-xs text-gray-500">Usia 0–5 tahun</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600">●</span></div></section>
        <section class="group rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Anak</p><p class="mt-2 text-3xl font-extrabold text-gray-900">{{ number_format($children) }}</p><p class="mt-1 text-xs text-gray-500">Usia 6–14 tahun</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">●</span></div></section>
        <section class="group rounded-2xl border border-indigo-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Usia Produktif</p><p class="mt-2 text-3xl font-extrabold text-gray-900">{{ number_format($productiveAge) }}</p><p class="mt-1 text-xs text-gray-500">Usia 15–64 tahun</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">●</span></div></section>
        <section class="group rounded-2xl border border-purple-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-wider text-purple-600">Lansia</p><p class="mt-2 text-3xl font-extrabold text-gray-900">{{ number_format($elderly) }}</p><p class="mt-1 text-xs text-gray-500">Usia 65+ tahun</p></div><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600">●</span></div></section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-orange-200 bg-white p-5 shadow-sm"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600">▤</div><div><h2 class="font-semibold text-gray-900">Statistik Pekerjaan</h2><p class="text-xs text-gray-500">Pekerjaan warga berdasarkan jumlah terbanyak</p></div></div><div class="mt-5 space-y-3">@forelse($occupations as $label => $total)<div class="rounded-lg border border-orange-100 bg-orange-50/70 p-3"><div class="mb-2 flex items-center justify-between gap-3"><span class="min-w-0 truncate text-sm font-medium text-gray-700">{{ $label ?: 'Tidak diisi' }}</span><span class="shrink-0 rounded-full bg-orange-500 px-2.5 py-1 text-xs font-bold text-white">{{ number_format($total) }}</span></div><div class="h-2 overflow-hidden rounded-full bg-orange-100"><div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-orange-500" style="width: {{ round(($total / max(1, (int) $occupations->max())) * 100, 2) }}%"></div></div></div>@empty<div class="rounded-lg border border-dashed border-orange-200 p-4 text-sm text-gray-500">Belum ada data pekerjaan.</div>@endforelse</div></section>
        <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm"><div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600">♥</div><div><h2 class="font-semibold text-gray-900">Status Perkawinan</h2><p class="text-xs text-gray-500">Distribusi status perkawinan warga</p></div></div><div class="mt-5 space-y-3">@forelse($marital as $label => $total)<div class="flex items-center justify-between rounded-lg border border-rose-100 bg-rose-50/70 px-3 py-3 shadow-sm"><span class="text-sm font-medium capitalize text-gray-700">{{ $label ?: 'Tidak diisi' }}</span><span class="rounded-full bg-rose-500 px-3 py-1 text-xs font-bold text-white shadow-sm">{{ number_format($total) }}</span></div>@empty<div class="rounded-lg border border-dashed border-rose-200 p-4 text-sm text-gray-500">Belum ada data perkawinan.</div>@endforelse</div></section>
    </div>

    @if($showAgePyramid)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="age-pyramid-title">
            <button type="button" wire:click="closeAgePyramid" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" aria-label="Tutup detail piramida"></button>
            <div class="relative z-10 flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                    <div>
                        <div class="flex items-center gap-2"><span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">▥</span><div><h2 id="age-pyramid-title" class="font-semibold text-gray-900">Piramida Penduduk</h2><p class="mt-0.5 text-xs text-gray-500">Distribusi penduduk menurut kelompok umur 5 tahunan dan jenis kelamin.</p></div></div>
                    </div>
                    <button type="button" wire:click="closeAgePyramid" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">×</button>
                </div>
                <div class="overflow-y-auto px-4 py-5 sm:px-6">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3">
                        <div class="flex gap-5 text-xs font-medium text-gray-600"><span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>Laki-laki <span class="text-gray-400">({{ number_format($male) }})</span></span><span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-pink-500"></span>Perempuan <span class="text-gray-400">({{ number_format($female) }})</span></span></div>
                        <span class="text-xs text-slate-500">{{ number_format($classifiedAge) }} penduduk terklasifikasi</span>
                    </div>
                    <div class="overflow-x-auto">
                        <div class="mx-auto min-w-[620px] max-w-5xl">
                            <div class="mb-3 grid grid-cols-[1fr_64px_1fr] items-center gap-2 text-xs font-semibold uppercase tracking-wide"><div class="text-right text-sky-600">Laki-laki</div><div></div><div class="text-pink-600">Perempuan</div></div>
                            <div class="space-y-1">@foreach($ageGroups as $label => $group)<div class="grid grid-cols-[1fr_64px_1fr] items-center gap-2"><div class="flex items-center justify-end gap-2"><span class="w-12 text-right text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($group['male']) }}</span><div class="h-6 flex-1 overflow-hidden rounded-l-md bg-slate-100"><div class="ml-auto h-full rounded-l-md bg-gradient-to-l from-sky-400 to-blue-600 transition-all duration-300" style="width: {{ $group['male_width'] }}%"></div></div></div><div class="rounded-md border border-slate-200 bg-slate-50 py-1 text-center text-[11px] font-bold text-slate-600">{{ $label }}</div><div class="flex items-center gap-2"><div class="h-6 flex-1 overflow-hidden rounded-r-md bg-slate-100"><div class="h-full rounded-r-md bg-gradient-to-r from-pink-400 to-rose-500 transition-all duration-300" style="width: {{ $group['female_width'] }}%"></div></div><span class="w-12 text-left text-[11px] font-semibold tabular-nums text-slate-500">{{ number_format($group['female']) }}</span></div></div>@endforeach</div>
                            <div class="mt-4 grid grid-cols-[1fr_64px_1fr] gap-2 text-[10px] text-slate-400"><div class="flex justify-between px-12"><span>0</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span></div><div></div><div class="flex justify-between px-12"><span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0</span></div></div>
                        </div>
                    </div>
                    @if($unclassifiedAge > 0)<div class="mt-4 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700"><span class="font-bold">{{ number_format($unclassifiedAge) }}</span> penduduk belum masuk piramida karena tanggal lahir belum tersedia.</div>@endif
                </div>
                <div class="flex shrink-0 justify-end border-t border-slate-100 bg-slate-50/70 px-5 py-3 sm:px-6">
                    <button type="button" wire:click="closeAgePyramid" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>
