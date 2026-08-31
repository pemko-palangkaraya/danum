@if($detail)
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kartu Keluarga</p>
                <h2 class="mt-1 font-mono text-lg font-semibold text-slate-900">{{ $detail->no_kk }}</h2>
                <p class="mt-1 text-sm text-slate-500">Kepala keluarga: {{ $detail->headCitizen?->nama_lengkap ?? 'Belum ditentukan' }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ $detail->alamat }}, RT {{ $detail->rt ?: '-' }} / RW {{ $detail->rw ?: '-' }}</p>
            </div>
            <button wire:click="$set('detailId', null)" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Tutup</button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIK</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Hubungan</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detail->activeMembers as $member)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-6 py-3.5 text-sm font-medium">{{ $member->citizen?->nama_lengkap }}</td>
                            <td class="px-6 py-3.5 font-mono text-sm text-slate-600">{{ $member->citizen?->nik }}</td>
                            <td class="px-6 py-3.5 text-sm text-slate-600">{{ $member->hubungan_dalam_keluarga }}</td>
                            <td class="px-6 py-3.5 text-right">
                                @if(auth()->user()->hasPermission('population.manage'))
                                    <button wire:click="removeMember('{{ $member->id }}')" class="text-sm font-semibold text-red-600 hover:text-red-700">Keluarkan</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada anggota aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('livewire.population.families._member-search')
    </div>
@endif
