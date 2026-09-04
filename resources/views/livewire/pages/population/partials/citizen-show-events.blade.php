<x-ui.card>
    <x-slot:header>
        <h2 class="font-semibold text-slate-900">Riwayat Peristiwa Kependudukan</h2>
    </x-slot:header>

    <div class="divide-y divide-slate-100 -mx-6 -my-6">
        @forelse($citizen->populationEvents as $event)
            <div class="flex flex-col gap-2 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ $event->event_type }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $event->notes ?: 'Tidak ada catatan.' }}</p>
                </div>
                <div class="text-sm text-slate-500">{{ $event->event_date?->format('d/m/Y') }}</div>
            </div>
        @empty
            <div class="px-6 py-10 text-center text-sm text-slate-500">Belum ada peristiwa kependudukan.</div>
        @endforelse
    </div>
</x-ui.card>
