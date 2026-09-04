<div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:block">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3">Jabatan</th>
                    @if($isSuperAdmin)<th class="px-5 py-3">Organisasi</th>@endif
                    <th class="px-5 py-3">Pejabat Aktif</th>
                    <th class="px-5 py-3">Penandatangan</th>
                    <th class="px-5 py-3">Verifikator</th>
                    <th class="px-5 py-3">Sertifikat TTE</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($positions as $position)
                    @php($holder = $position->holders->first(fn ($item) => $item->ended_at === null && $item->started_at?->lte(now())))
                    @php($certificate = $position->signerCertificates->first())
                    <tr>
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-900">{{ $position->name }}</div>
                            <div class="text-xs text-slate-500">{{ $position->code }}</div>
                        </td>
                        @if($isSuperAdmin)<td class="px-5 py-4 text-sm text-slate-700">{{ $position->tenant?->name ?? '—' }}</td>@endif
                        <td class="px-5 py-4 text-slate-700">
                            @if($holder?->user)
                                <div class="font-medium">{{ $holder->user->name }}</div>
                                <div class="text-xs text-slate-500">Mulai {{ $holder->started_at?->format('d M Y') }}</div>
                            @else
                                <span class="text-slate-400">Belum ditentukan</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">@if($position->can_sign)<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Boleh TTE</span>@else<span class="text-slate-400">Tidak</span>@endif</td>
                        <td class="px-5 py-4">@if($position->can_validate)<span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Boleh Verifikasi</span>@else<span class="text-slate-400">Tidak</span>@endif</td>
                        <td class="px-5 py-4">
                            @if(!$position->can_sign)
                                <span class="text-slate-400">Tidak diperlukan</span>
                            @elseif($certificate && $certificate->isUsable())
                                <div class="font-medium text-emerald-700">Aktif</div>
                                <div class="text-xs text-slate-500">s.d. {{ $certificate->valid_until?->format('d M Y') }}</div>
                            @else
                                <span class="text-amber-600">Belum ada</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $position->status->value === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $position->status->value === 'active' ? 'Aktif' : 'Tidak Aktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @include('livewire.pages.positions._action-menu')
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada jabatan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-ui.table-footer :paginator="$positions" label="jabatan" />
</div>
