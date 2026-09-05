@if ($isSuperAdmin)
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-slate-900">Kondisi organisasi</h2>
                <p class="mt-1 text-xs text-slate-500">Ringkasan tenant terbaru di platform.</p>
            </div>
            @if (Route::has('tenants.index'))
                <a href="{{ route('tenants.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">Kelola →</a>
            @endif
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-400">
                    <tr>
                        <th class="pb-3 pr-4 font-medium">Organisasi</th>
                        <th class="pb-3 pr-4 font-medium">Status</th>
                        <th class="pb-3 pr-4 font-medium">Pengguna</th>
                        <th class="pb-3 font-medium">Surat Terbit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tenantBreakdown as $row)
                        <tr>
                            <td class="py-3 pr-4 font-medium text-slate-800">{{ $row['name'] }}</td>
                            <td class="py-3 pr-4 text-slate-500">{{ $row['status'] }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ number_format($row['users']) }}</td>
                            <td class="py-3 text-slate-600">{{ number_format($row['issued']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-sm text-slate-400">Belum ada organisasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif
