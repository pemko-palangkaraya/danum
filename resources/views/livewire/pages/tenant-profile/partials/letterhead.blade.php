<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-slate-900">Kop Surat</h2>
        <p class="mt-1 text-xs text-slate-500">Kop surat resmi yang digunakan pada PDF surat yang diterbitkan.</p>
    </div>

    <div class="p-5 sm:p-6">
        <div class="flex min-h-32 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
            @if ($letterheadUrl)
                <img src="{{ $letterheadUrl }}" alt="Kop surat saat ini" class="max-h-32 max-w-full object-contain">
            @else
                <div class="text-center text-xs text-slate-400">Belum ada kop surat</div>
            @endif
        </div>
    </div>
</section>
