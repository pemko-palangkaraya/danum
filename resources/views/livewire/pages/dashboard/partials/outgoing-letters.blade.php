<section class="relative overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-cyan-50 shadow-sm">
    <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-indigo-200/40 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-cyan-200/30 blur-3xl"></div>

    <div class="relative p-6 sm:p-8">
        <div class="flex flex-col gap-7 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Administrasi</p>
                    <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900">Ringkasan surat keluar</h2>
                    <p class="mt-1 text-sm text-slate-500">Data surat keluar dan status pengiriman {{ $isSuperAdmin ? 'seluruh platform' : 'di '.$tenantName }}.</p>
                </div>
                <a href="{{ route('outgoing-letters.index') }}" class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-xl border border-indigo-200 bg-indigo-50 px-4 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:self-auto">
                    Lihat surat keluar <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:w-[520px] xl:shrink-0">
                @foreach ($controlCards as $card)
                <div class="rounded-2xl border border-white/80 bg-white/75 p-4 shadow-sm backdrop-blur-sm">
                    <p class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($card['value']) }}</p>
                    <p class="mt-1 text-[11px] font-semibold text-slate-600">{{ $card['label'] }}</p>
                    <p class="mt-2 text-[10px] leading-4 text-slate-400">{{ $card['hint'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>