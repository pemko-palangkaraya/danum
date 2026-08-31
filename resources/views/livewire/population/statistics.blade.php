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

    <div class="grid gap-6 lg:grid-cols-2">
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
            <h2 class="font-semibold text-gray-900 dark:text-white">Kelompok Umur</h2>
            <div class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-5">
                @foreach($ageGroups as $label => $total)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900"><p class="text-xs text-gray-500">{{ $label }}</p><p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ number_format($total) }}</p></div>
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
