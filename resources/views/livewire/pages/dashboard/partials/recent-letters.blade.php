<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h2 class="font-semibold text-slate-900">Surat terbaru</h2>
            <p class="mt-1 text-xs text-slate-500">{{ $isSuperAdmin ? 'Aktivitas surat seluruh platform.' : 'Aktivitas surat dalam organisasi ini.' }}</p>
        </div>
        @can('viewAny', App\Models\OutgoingLetter::class)
            <a href="{{ route('outgoing-letters.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">Lihat semua →</a>
        @endcan
    </div>

    <div class="mt-5 space-y-2">
        @forelse ($recentLetters as $letter)
            <a href="{{ route('outgoing-letters.show', $letter['id']) }}" class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-slate-800">{{ $letter['subject'] }}</p>
                    <p class="mt-1 truncate text-xs text-slate-400">{{ $letter['number'] }} · {{ $letter['owner'] }}</p>
                </div>
                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase {{ $letter['statusClass'] }}">{{ $letter['status'] }}</span>
            </a>
        @empty
            <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Belum ada surat.</div>
        @endforelse
    </div>
</section>
