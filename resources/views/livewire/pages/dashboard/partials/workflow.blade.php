<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold text-slate-900">Status workflow</h2>
            <p class="mt-1 text-xs text-slate-500">Status surat: Draft → Verifikasi → Siap TTE → Terbit.</p>
        </div>
        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700">Live</span>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-4">
        @foreach ($workflow as $step)
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-medium text-slate-500">{{ $step['label'] }}</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($step['value']) }}</p>
            </div>
        @endforeach
    </div>

    @if ($stats['letters'] > 0)
        <div class="mt-5 flex h-2 overflow-hidden rounded-full bg-slate-100">
            @foreach ($workflow as $step)
                <span class="h-full bg-slate-900" style="width: {{ min(100, ($step['value'] / $stats['letters']) * 100) }}%"></span>
            @endforeach
        </div>
    @endif
</section>
