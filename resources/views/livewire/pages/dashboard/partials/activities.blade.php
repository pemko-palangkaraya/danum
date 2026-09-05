<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div>
        <h2 class="font-semibold text-slate-900">Aktivitas terbaru</h2>
        <p class="mt-1 text-xs text-slate-500">Audit log sesuai scope akun.</p>
    </div>

    <div class="mt-5 space-y-2">
        @forelse ($activities as $activity)
            <div class="flex gap-3 rounded-xl border border-slate-100 px-4 py-3">
                <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-slate-400"></div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-700">{{ $activity['action'] }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ $activity['actor'] }} · {{ $activity['when'] }}</p>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Belum ada aktivitas.</div>
        @endforelse
    </div>
</section>
