@if($showHistory)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showHistory', false)">
        <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">Riwayat Pejabat</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $historyPositionName }}</p>
                </div>
                <button type="button" wire:click="$set('showHistory', false)" class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-100">✕</button>
            </div>

            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr><th class="px-4 py-3">Pejabat</th><th class="px-4 py-3">Mulai</th><th class="px-4 py-3">Berakhir</th><th class="px-4 py-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($history as $holder)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-800">{{ $holder->user?->name ?? 'User dihapus' }}</td>
                                <td class="px-4 py-3">{{ $holder->started_at?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $holder->ended_at?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    @if($holder->ended_at === null)<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Aktif</span>@else<span class="text-slate-500">Selesai</span>@endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada riwayat pejabat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="button" wire:click="$set('showHistory', false)" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tutup</button>
            </div>
        </div>
    </div>
@endif
