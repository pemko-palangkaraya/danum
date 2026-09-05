<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">No. KK</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Kepala Keluarga</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Alamat</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Anggota</th>
                    <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($families as $family)
                    <tr class="transition hover:bg-slate-50/70">
                        <td class="whitespace-nowrap px-6 py-4 font-mono text-sm font-semibold text-slate-900">{{ $family->no_kk }}</td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-800">{{ $family->headCitizen?->nama_lengkap ?? '-' }}</td>
                        <td class="max-w-md px-6 py-4 text-sm text-slate-600">{{ $family->alamat }}</td>
                        <td class="px-6 py-4 text-center"><span class="inline-flex min-w-8 justify-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $family->active_members_count }}</span></td>
                        <td class="px-6 py-4 text-right">
                            <x-ui.family-actions :family="$family" :can-manage="$canManage" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-14 text-center">
                            <p class="text-sm font-medium text-slate-700">Belum ada data kartu keluarga</p>
                            <p class="mt-1 text-sm text-slate-500">Data KK akan muncul di sini setelah ditambahkan.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.table-footer :paginator="$families" label="KK" />
</div>
