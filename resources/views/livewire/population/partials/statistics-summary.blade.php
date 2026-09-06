<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="group relative overflow-hidden rounded-2xl border border-blue-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-blue-500/10 blur-2xl"></div>
        <div class="relative flex items-start justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Penduduk Hidup</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalCitizens) }}</p><p class="mt-1 text-xs text-gray-500">Penduduk hidup tahun {{ $currentYear }}</p></div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">👥</span>
        </div>
    </div>
    <div class="group relative overflow-hidden rounded-2xl border border-violet-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-violet-500/10 blur-2xl"></div>
        <div class="relative flex items-start justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total KK</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($totalFamilies) }}</p><p class="mt-1 text-xs text-gray-500">Kartu keluarga</p></div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">⌂</span>
        </div>
    </div>
    <div class="group relative overflow-hidden rounded-2xl border border-rose-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-rose-500/10 blur-2xl"></div>
        <div class="relative flex items-start justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Meninggal Tahun Ini</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($deceasedThisYear) }}</p><p class="mt-1 text-xs text-rose-600">Data kematian {{ $currentYear }}</p></div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600">†</span>
        </div>
    </div>
    <div class="group relative overflow-hidden rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-emerald-500/10 blur-2xl"></div>
        <div class="relative flex items-start justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Lahir Tahun Ini</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($birthsThisYear) }}</p><p class="mt-1 text-xs text-emerald-600">Data kelahiran {{ $currentYear }}</p></div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">+</span>
        </div>
    </div>
    <div class="group relative overflow-hidden rounded-2xl border border-sky-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-sky-500/10 blur-2xl"></div>
        <div class="relative flex items-start justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Laki-laki</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($male) }}</p><p class="mt-1 text-xs text-sky-600">{{ $totalCitizens ? number_format(($male / $totalCitizens) * 100, 1) : 0 }}% dari penduduk hidup</p></div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-600">♂</span>
        </div>
    </div>
    <div class="group relative overflow-hidden rounded-2xl border border-pink-100 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg">
        <div class="absolute right-0 top-0 h-24 w-24 rounded-full bg-pink-500/10 blur-2xl"></div>
        <div class="relative flex items-start justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Perempuan</p><p class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">{{ number_format($female) }}</p><p class="mt-1 text-xs text-pink-600">{{ $totalCitizens ? number_format(($female / $totalCitizens) * 100, 1) : 0 }}% dari penduduk hidup</p></div>
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-pink-50 text-pink-600">♀</span>
        </div>
    </div>
</div>
