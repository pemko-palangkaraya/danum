<div class="grid gap-4 lg:grid-cols-2">
    <section class="rounded-2xl border border-orange-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600">▤</div><div><h2 class="font-semibold text-gray-900">Statistik Pekerjaan</h2><p class="text-xs text-gray-500">Pekerjaan warga berdasarkan jumlah terbanyak</p></div></div>
        <div class="mt-4 flex flex-wrap gap-2">
            @forelse($occupations as $label => $total)
                <span class="inline-flex max-w-full items-center gap-2 rounded-full border border-orange-100 bg-orange-50 px-3 py-1.5 text-xs font-medium text-gray-700" title="{{ $label ?: 'Tidak diisi' }}"><span class="max-w-[15rem] truncate">{{ $label ?: 'Tidak diisi' }}</span><span class="rounded-full bg-orange-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ number_format($total) }}</span></span>
            @empty
                <div class="rounded-lg border border-dashed border-orange-200 p-4 text-sm text-gray-500">Belum ada data pekerjaan.</div>
            @endforelse
        </div>
    </section>
    <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm">
        <div class="flex items-center gap-3"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600">♥</div><div><h2 class="font-semibold text-gray-900">Status Perkawinan</h2><p class="text-xs text-gray-500">Distribusi status perkawinan warga</p></div></div>
        <div class="mt-4 flex flex-wrap gap-2">
            @forelse($marital as $label => $total)
                <span class="inline-flex items-center gap-2 rounded-full border border-rose-100 bg-rose-50 px-3 py-1.5 text-xs font-medium capitalize text-gray-700"><span>{{ $label ?: 'Tidak diisi' }}</span><span class="rounded-full bg-rose-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ number_format($total) }}</span></span>
            @empty
                <div class="rounded-lg border border-dashed border-rose-200 p-4 text-sm text-gray-500">Belum ada data perkawinan.</div>
            @endforelse
        </div>
    </section>
</div>