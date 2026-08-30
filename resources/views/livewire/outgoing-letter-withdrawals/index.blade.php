<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('outgoing-letters.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">← Outgoing Letters</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Penarikan Surat</h1>
            <p class="mt-1 text-sm text-slate-500">Pengajuan penarikan surat yang sudah diterbitkan dan proses persetujuannya.</p>
        </div>
        @if($isSuperAdmin)
            <span class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800">SUPER ADMIN</span>
        @endif
    </div>

    @if($isSuperAdmin)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Menunggu Persetujuan</h2>
                    <p class="mt-1 text-xs text-slate-500">Setiap pengajuan harus diperiksa sebelum surat berubah menjadi Ditarik.</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search withdrawal..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">
                </div>
            </div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Surat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Organisasi</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Pemohon</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pendingRequests as $request)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs font-semibold text-slate-400">{{ $request->outgoingLetter->number }}</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $request->outgoingLetter->subject }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $request->outgoingLetter->recipient_name }}</div>
                                    <div class="mt-2 max-w-sm truncate text-xs text-rose-700" title="{{ $request->reason }}">Alasan: {{ $request->reason }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $request->outgoingLetter->tenant?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-800">{{ $request->requestedBy?->name ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ optional($request->requested_at)->format('d M Y H:i') }}</div>
                                </td>
                                <td class="px-6 py-4"><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('outgoing-letters.show', $request->outgoingLetter->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Lihat Surat</a>
                                        <button wire:click="openDecision('{{ $request->id }}')" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Proses</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">{{ trim($search) !== '' ? 'Tidak ada pengajuan yang cocok dengan pencarian.' : 'Tidak ada pengajuan penarikan yang menunggu.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                @forelse($pendingRequests as $request)
                    <div class="p-5">
                        <div class="flex flex-col gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-semibold text-slate-400">{{ $request->outgoingLetter->number }}</span><span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span></div>
                                <h3 class="mt-1 font-semibold text-slate-900">{{ $request->outgoingLetter->subject }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $request->outgoingLetter->tenant?->name }} · {{ $request->outgoingLetter->recipient_name }}</p>
                                <p class="mt-2 text-xs text-slate-500">Pemohon: {{ $request->requestedBy?->name ?? '—' }} · {{ optional($request->requested_at)->format('d M Y H:i') }}</p>
                                <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-900"><span class="font-semibold">Alasan:</span> {{ $request->reason }}</div>
                                <p class="mt-2 text-xs text-slate-500">Surat pernyataan: <a href="{{ route('outgoing-letter-withdrawals.statement', $request->id) }}" class="font-semibold text-slate-700 underline decoration-slate-300 underline-offset-2 hover:text-slate-900">Unduh PDF bertanda tangan</a></p>
                            </div>
                            <div class="flex gap-2"><a href="{{ route('outgoing-letters.show', $request->outgoingLetter->id) }}" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">Lihat Surat</a><button wire:click="openDecision('{{ $request->id }}')" class="flex-1 rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Proses</button></div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-sm text-slate-500">{{ trim($search) !== '' ? 'Tidak ada pengajuan yang cocok dengan pencarian.' : 'Tidak ada pengajuan penarikan yang menunggu.' }}</div>
                @endforelse
            </div>

            <x-ui.table-footer :paginator="$pendingRequests" per-page-model="pendingPerPage" label="pengajuan" />
        </section>
    @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Surat Terbit Milik Saya</h2>
                    <p class="mt-1 text-xs text-slate-500">Penarikan hanya dapat diajukan oleh pembuat surat.</p>
                </div>
                <div class="relative w-full sm:w-80">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search letter..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">
                </div>
            </div>

            <div class="hidden overflow-x-auto lg:block">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Surat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Penerima</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Jenis Surat</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($issuedLetters as $letter)
                            @php($pending = $letter->withdrawalRequests->first(fn($r) => $r->status->value === 'pending'))
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-mono text-xs font-semibold text-slate-400">{{ $letter->number }}</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $letter->subject }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $letter->recipient_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-700">{{ $letter->letterType?->name ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    @if($pending)
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Menunggu</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Issued</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('outgoing-letters.show', $letter->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Lihat Surat</a>
                                        @if($pending)
                                            <span class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-700">Menunggu Persetujuan</span>
                                        @else
                                            <button wire:click="openRequest('{{ $letter->id }}')" class="rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">Ajukan Penarikan</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">{{ trim($search) !== '' ? 'Tidak ada surat yang cocok dengan pencarian.' : 'Belum ada surat terbit yang dapat diajukan untuk penarikan.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-slate-100 lg:hidden">
                @forelse($issuedLetters as $letter)
                    @php($pending = $letter->withdrawalRequests->first(fn($r) => $r->status->value === 'pending'))
                    <div class="p-5">
                        <div class="flex flex-col gap-4">
                            <div>
                                <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-semibold text-slate-400">{{ $letter->number }}</span>@if($pending)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Menunggu</span>@else<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Issued</span>@endif</div>
                                <h3 class="mt-1 font-semibold text-slate-900">{{ $letter->subject }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ $letter->recipient_name }} · {{ $letter->letterType?->name }}</p>
                            </div>
                            <div class="flex gap-2"><a href="{{ route('outgoing-letters.show', $letter->id) }}" class="flex-1 rounded-lg border border-slate-200 px-3 py-2 text-center text-sm font-medium text-slate-700 hover:bg-slate-50">Lihat Surat</a>@if($pending)<span class="flex-1 rounded-lg bg-amber-50 px-3 py-2 text-center text-sm font-semibold text-amber-700">Menunggu</span>@else<button wire:click="openRequest('{{ $letter->id }}')" class="flex-1 rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">Ajukan Penarikan</button>@endif</div>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center text-sm text-slate-500">{{ trim($search) !== '' ? 'Tidak ada surat yang cocok dengan pencarian.' : 'Belum ada surat terbit yang dapat diajukan untuk penarikan.' }}</div>
                @endforelse
            </div>

            <x-ui.table-footer :paginator="$issuedLetters" per-page-model="perPage" label="surat terbit" />
        </section>

        @if($showRequestForm)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showRequestForm', false)">
                <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                    <h2 class="text-lg font-semibold text-slate-900">Ajukan Penarikan Surat</h2>
                    <p class="mt-1 text-sm text-slate-500">Pengajuan akan dikirim ke Super Admin. Surat tetap resmi sampai pengajuan disetujui.</p>
                    @if($selectedLetter)<div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm"><div class="font-mono text-xs text-slate-400">{{ $selectedLetter->number }}</div><div class="mt-1 font-semibold text-slate-900">{{ $selectedLetter->subject }}</div></div>@endif
                    <div class="mt-5 space-y-4">
                        <div><label class="text-sm font-medium text-slate-700">Alasan Penarikan</label><textarea wire:model="reason" rows="5" class="form-textarea mt-1" placeholder="Jelaskan alasan resmi penarikan surat..."></textarea>@error('reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Surat Pernyataan Bertanda Tangan</label><input wire:model="statementFile" type="file" accept="application/pdf,.pdf" class="form-control mt-1"><p class="mt-1 text-xs text-slate-500">Wajib PDF yang sudah ditandatangani pemohon (dan dibubuhi stempel bila diperlukan). Maksimal 10 MB.</p><p class="mt-1 text-xs text-amber-700">DANUM menyimpan dokumen ini sebagai bukti administratif; tanda tangan digital tersertifikasi belum diverifikasi otomatis oleh sistem.</p>@error('statementFile')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2"><button wire:click="$set('showRequestForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button wire:click="submitRequest" type="button" wire:loading.attr="disabled" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">Kirim Pengajuan</button></div>
                </div>
            </div>
        @endif
    @endif

    @if($showDecisionForm)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showDecisionForm', false)">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-semibold text-slate-900">Proses Penarikan</h2><p class="mt-1 text-sm text-slate-500">Pilih persetujuan atau penolakan. Keterangan keputusan Super Admin wajib diisi untuk menjaga audit trail.</p><div class="mt-5"><label class="text-sm font-medium text-slate-700">Keterangan Keputusan</label><textarea wire:model="decisionNote" rows="5" class="form-textarea mt-1" placeholder="Jelaskan alasan keputusan Super Admin..."></textarea>@error('decisionNote')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div class="mt-6 flex justify-end gap-2"><button wire:click="$set('showDecisionForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button wire:click="reject" type="button" class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700">Tolak</button><button wire:click="approve" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Setujui Penarikan</button></div></div>
        </div>
    @endif
</div>
