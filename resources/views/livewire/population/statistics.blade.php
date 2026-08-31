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
        @foreach([
            ['Total Penduduk', $totalCitizens], ['Total KK', $totalFamilies],
            ['Laki-laki', $male], ['Perempuan', $female],
        ] as [$label, $value])
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Piramida penduduk: laki-laki di kiri, perempuan di kanan --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-semibold text-gray-900 dark:text-white">Piramida Penduduk</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Distribusi penduduk berdasarkan kelompok umur dan jenis kelamin.</p>
            </div>
            <div class="flex gap-5 text-xs text-gray-500 dark:text-gray-400">
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-sm bg-slate-400"></span>Laki-laki</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-sm bg-slate-200 ring-1 ring-slate-300"></span>Perempuan</span>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <div class="mx-auto min-w-[620px] max-w-5xl">
                <div class="mb-3 grid grid-cols-[1fr_56px_1fr] items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <div class="text-right">Laki-laki <span class="font-normal normal-case">({{ number_format($male) }})</span></div>
                    <div></div>
                    <div>Perempuan <span class="font-normal normal-case">({{ number_format($female) }})</span></div>
                </div>

                <div class="space-y-1">
                    @foreach($ageGroups as $label => $group)
                        <div class="grid grid-cols-[1fr_56px_1fr] items-center gap-2">
                            {{-- Bar laki-laki mengembang ke kiri --}}
                            <div class="flex items-center justify-end gap-2">
                                <span class="w-12 text-right text-[11px] tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ number_format($group['male']) }}
                                </span>
                                <div class="h-6 flex-1 overflow-hidden bg-gray-50 dark:bg-gray-900">
                                    <div class="ml-auto h-full rounded-l-sm bg-slate-400" style="width: {{ $group['male_width'] }}%"></div>
                                </div>
                            </div>

                            <div class="text-center text-[11px] font-medium text-gray-600 dark:text-gray-300">{{ $label }}</div>

                            {{-- Bar perempuan mengembang ke kanan --}}
                            <div class="flex items-center gap-2">
                                <div class="h-6 flex-1 overflow-hidden bg-gray-50 dark:bg-gray-900">
                                    <div class="h-full rounded-r-sm bg-slate-200 ring-1 ring-inset ring-slate-300" style="width: {{ $group['female_width'] }}%"></div>
                                </div>
                                <span class="w-12 text-left text-[11px] tabular-nums text-gray-500 dark:text-gray-400">
                                    {{ number_format($group['female']) }}
                                </span>
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

        @php
            $otherAgeGender = $ageGroups->sum('other');
        @endphp
        @if($otherAgeGender > 0)
            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                {{ number_format($otherAgeGender) }} penduduk memiliki jenis kelamin yang belum dapat diklasifikasikan sebagai laki-laki/perempuan.
            </p>
        @endif
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-white">Status Kependudukan</h2>
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div><p class="text-sm text-gray-500">Aktif</p><p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($activeCitizens) }}</p></div>
                <div><p class="text-sm text-gray-500">Nonaktif</p><p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($inactiveCitizens) }}</p></div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-white">Jenis Kelamin</h2>
            <div class="mt-4 space-y-3">
                @foreach($gender as $label => $total)
                    <div class="flex items-center justify-between text-sm"><span class="text-gray-600 dark:text-gray-300">{{ $label ?: 'Tidak diisi' }}</span><span class="font-medium text-gray-900 dark:text-white">{{ number_format($total) }}</span></div>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="font-semibold text-gray-900 dark:text-white">Status Perkawinan</h2>
            <div class="mt-4 space-y-3">
                @foreach($marital as $label => $total)
                    <div class="flex items-center justify-between text-sm"><span class="text-gray-600 dark:text-gray-300">{{ $label ?: 'Tidak diisi' }}</span><span class="font-medium text-gray-900 dark:text-white">{{ number_format($total) }}</span></div>
                @endforeach
            </div>
        </section>
    </div>
</div>
