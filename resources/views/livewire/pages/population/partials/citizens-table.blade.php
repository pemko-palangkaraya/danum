<x-ui.table-shell :responsive="false">
    <x-slot:toolbar>
        @include('livewire.pages.population.partials.citizens-filters', [
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $tenants,
        ])
    </x-slot:toolbar>

    @if($tenantSelected)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIK</th>
                        <th class="hidden px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 md:table-cell">Tempat, Tanggal Lahir</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($citizens as $citizen)
                        <tr class="transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-slate-900">{{ $citizen->nama_lengkap }}</td>
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-slate-600">{{ $citizen->nik }}</td>
                            <td class="hidden px-6 py-4 text-sm text-slate-600 md:table-cell">{{ $citizen->tempat_lahir ?: '-' }}, {{ $citizen->tanggal_lahir?->format('d/m/Y') ?: '-' }}</td>
                            <td class="px-6 py-4"><x-ui.badge variant="success">{{ ucfirst($citizen->status_kependudukan) }}</x-ui.badge></td>
                            <td class="px-6 py-4 text-right">
                                <x-ui.citizen-actions
                                    :citizen="$citizen"
                                    :detail-route="$detailRoute"
                                    :can-manage="$canManage"
                                />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-14"><x-ui.empty-state title="Belum ada data warga" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-slot:footer>
            <x-ui.table-footer :paginator="$citizens" label="warga" />
        </x-slot:footer>
    @else
        <div class="px-6 py-14 text-center">
            <x-ui.empty-state title="Pilih tenant terlebih dahulu" />
        </div>
    @endif
</x-ui.table-shell>
