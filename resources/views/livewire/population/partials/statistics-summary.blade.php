<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <x-ui.card>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total Penduduk</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalCitizens) }}</p>
        <p class="mt-1 text-xs text-gray-500">Warga terdata</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total KK</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalFamilies) }}</p>
        <p class="mt-1 text-xs text-gray-500">Kartu keluarga</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Laki-laki</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($male) }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ $totalCitizens ? number_format(($male / $totalCitizens) * 100, 1) : 0 }}% dari penduduk</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Perempuan</p>
        <p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($female) }}</p>
        <p class="mt-1 text-xs text-gray-500">{{ $totalCitizens ? number_format(($female / $totalCitizens) * 100, 1) : 0 }}% dari penduduk</p>
    </x-ui.card>
</div>