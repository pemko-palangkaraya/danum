<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Statistik Kependudukan</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ringkasan data penduduk dan keluarga.</p>
        </div>
        @if(auth()->user()->isSuperAdmin())
            <select wire:model.live="selectedTenantId" class="rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                <option value="">Pilih tenant</option>
                @foreach(\App\Models\Tenant::query()->orderBy('name')->get() as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="overflow-hidden rounded-xl border border-blue-200 bg-gradient-to-br from-blue-500 to-blue-700 p-5 shadow-sm dark:border-blue-800">
            <p class="text-sm font-medium text-blue-100">Total Penduduk</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ number_format($totalCitizens) }}</p>
            <p class="mt-1 text-xs text-blue-100">Seluruh warga terdata</p>
        </div>
        <div class="overflow-hidden rounded-xl border border-violet-200 bg-gradient-to-br from-violet-500 to-violet-700 p-5 shadow-sm dark:border-violet-800">
            <p class="text-sm font-medium text-violet-100">Total KK</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ number_format($totalFamilies) }}</p>
            <p class="mt-1 text-xs text-violet-100">Kartu keluarga terdata</p>
        </div>
        <div class="overflow-hidden rounded-xl border border-sky-200 bg-gradient-to-br from-sky-500 to-cyan-600 p-5 shadow-sm dark:border-sky-800">
            <p class="text-sm font-medium text-sky-100">Laki-laki</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ number_format($male) }}</p>
            <p class="mt-1 text-xs text-sky-100">Penduduk laki-laki</p>
        </div>
        <div class="overflow-hidden rounded-xl border border-pink-200 bg-gradient-to-br from-pink-500 to-rose-600 p-5 shadow-sm dark:border-pink-800">
            <p class="text-sm font-medium text-pink-100">Perempuan</p>
            <p class="mt-2 text-3xl font-bold text-white">{{ number_format($female) }}</p>
            <p class="mt-1 text-xs text-pink-100">Penduduk perempuan</p>
        </div>
    </div>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Piramida Penduduk</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jumlah penduduk menurut kelompok umur 5 tahunan dan jenis kelamin.</p>
            </div>
            <div class="flex gap-5 text-xs text-gray-600 dark:text-gray-300">
                <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-sky-500"></span>Laki-laki</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-2.5 w-2.5 rounded-sm bg-pink-500"></span>Perempuan</span>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <div class="mx-auto min-w-[620px] max-w-5xl">
                <div class="mb-3 grid grid-cols-[1fr_56px_1fr] items-center gap-2 text-xs font-semibold uppercase tracking-wide">
                    <div class="text-right text-sky-600 dark:text-sky-400">Laki-laki <span class="font-normal normal-case text-gray-500">({{ number_format($male) }})</span></div>
                    <div></div>
                    <div class="text-pink-600 dark:text-pink-400">Perempuan <span class="font-normal normal-case text-gray-500">({{ number_format($female) }})</span></div>
                </div>

                <div class="space-y-1">
                    @foreach($ageGroups as $label => $group)
                        <div class="grid grid-cols-[1fr_56px_1fr] items-center gap-2">
                            <div class="flex items-center justify-end gap-2">
                                <span class="w-12 text-right text-[11px] font-medium tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($group['male']) }}</span>
                                <div class="h-6 flex-1 overflow-hidden rounded-l-md bg-sky-50 dark:bg-slate-900">
                                    <div class="ml-auto h-full rounded-l-md bg-gradient-to-l from-sky-400 to-sky-600 transition-all duration-300" style="width: {{ $group['male_width'] }}%"></div>
                                </div>
                            </div>
                            <div class="rounded-md bg-gray-100 py-1 text-center text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ $label }}</div>
                            <div class="flex items-center gap-2">
                                <div class="h-6 flex-1 overflow-hidden rounded-r-md bg-pink-50 dark:bg-slate-900">
                                    <div class="h-full rounded-r-md bg-gradient-to-r from-pink-400 to-rose-500 transition-all duration-300" style="width: {{ $group['female_width'] }}%"></div>
                                </div>
                                <span class="w-12 text-left text-[11px] font-medium tabular-nums text-gray-600 dark:text-gray-300">{{ number_format($group['female']) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-[1fr_56px_1fr] gap-2 text-[10px] text-gray-400 dark:text-gray-500">
                    <div class="flex justify-between px-12"><span>0</span><span>25%</span><span>50%</span><span>75%</span><span>100%</span></div>
                    <div></div>
                    <div class="flex justify-between px-12"><span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0</span></div>
                </div>
            </div>
        </div>

        @php $otherAgeGender = $ageGroups->sum('other'); @endphp
        @if($otherAgeGender > 0)
            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                {{ number_format($otherAgeGender) }} penduduk memiliki jenis kelamin yang belum dapat diklasifikasikan sebagai laki-laki/perempuan.
            </p>
        @endif
    </section>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <section class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-5 shadow-sm dark:border-amber-900 dark:from-amber-900/30 dark:to-orange-900/20">
            <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Balita</p>
            <p class="mt-2 text-2xl font-bold text-amber-900 dark:text-amber-100">{{ number_format($toddlers) }}</p>
            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Usia 0–5 tahun</p>
        </section>
        <section class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-green-50 p-5 shadow-sm dark:border-emerald-900 dark:from-emerald-900/30 dark:to-green-900/20">
            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Anak</p>
            <p class="mt-2 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ number_format($children) }}</p>
            <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Usia 6–12 tahun</p>
        </section>
        <section class="rounded-xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-blue-50 p-5 shadow-sm dark:border-indigo-900 dark:from-indigo-900/30 dark:to-blue-900/20">
            <p class="text-sm font-medium text-indigo-700 dark:text-indigo-300">Usia Produktif</p>
            <p class="mt-2 text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ number_format($productiveAge) }}</p>
            <p class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">Usia 15–64 tahun</p>
        </section>
        <section class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-50 to-fuchsia-50 p-5 shadow-sm dark:border-purple-900 dark:from-purple-900/30 dark:to-fuchsia-900/20">
            <p class="text-sm font-medium text-purple-700 dark:text-purple-300">Lansia</p>
            <p class="mt-2 text-2xl font-bold text-purple-900 dark:text-purple-100">{{ number_format($elderly) }}</p>
            <p class="mt-1 text-xs text-purple-600 dark:text-purple-400">Usia 65+ tahun</p>
        </section>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">✓</div>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Status Kependudukan</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Kondisi data warga saat ini</p>
                </div>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-4">
                <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-900/20">
                    <p class="text-sm text-emerald-700 dark:text-emerald-300">Aktif</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ number_format($activeCitizens) }}</p>
                </div>
                <div class="rounded-lg bg-gray-100 p-4 dark:bg-gray-700/50">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Nonaktif</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($inactiveCitizens) }}</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">♥</div>
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">Status Perkawinan</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Distribusi status perkawinan warga</p>
                </div>
            </div>
            <div class="mt-5 space-y-3">
                @forelse($marital as $label => $total)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2.5 dark:bg-gray-700/40">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ $label ?: 'Tidak diisi' }}</span>
                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ number_format($total) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada data.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
