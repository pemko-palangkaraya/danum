@if ($canViewPopulation && $population)
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Kependudukan</p>
                <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Ringkasan warga</h2>
                <p class="mt-1 text-sm text-slate-500">Data penduduk dan kartu keluarga {{ $isSuperAdmin ? 'seluruh platform' : 'di '.$tenantName }}.</p>
            </div>
            <a href="{{ $isSuperAdmin ? route('population.admin.statistics') : route('population.statistics') }}" class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:self-auto">
                Lihat statistik warga <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($populationCards as $card)
                <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-{{ $card['tone'] }}-50/60 to-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-xs text-{{ $card['tone'] }}-600">{{ $card['hint'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
