<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('outgoing-letters.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">← Outgoing Letters</a>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $letter->subject }}</h1>
                @php($status = $letter->status->value)
                @php($submitted = $letter->submitted_at !== null)
                @php($effectiveState = $status === 'withdrawn' ? 'withdrawn' : ($status === 'issued' && $letter->isExpired() ? 'expired' : $status))
                <span @class(['inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide','bg-blue-100 text-blue-800' => $status === 'draft' && $submitted,'bg-slate-100 text-slate-700' => $status === 'draft' && ! $submitted,'bg-amber-100 text-amber-800' => $status === 'validated','bg-emerald-100 text-emerald-800' => $status === 'issued' && $effectiveState !== 'expired','bg-amber-100 text-amber-800' => $effectiveState === 'expired','bg-red-100 text-red-800' => in_array($status, ['cancelled', 'withdrawn'], true)])>{{ $status === 'draft' && $submitted ? 'Menunggu Verifikasi' : match($effectiveState) { 'withdrawn' => 'Ditarik', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Cancelled', 'issued' => 'Issued', 'validated' => 'Terverifikasi', default => 'Draft' } }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ $letter->number }} · {{ $letter->letterType?->name }}</p>
        </div>
        <div class="flex flex-wrap gap-2"><a href="{{ route('outgoing-letters.pdf', $letter->id) }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $status === 'issued' ? 'View PDF' : 'Preview PDF' }}</a>@if ($status === 'issued')<a href="{{ route('outgoing-letters.pdf', ['id' => $letter->id, 'download' => 1]) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Download Issued PDF</a>@endif<a href="{{ route('outgoing-letters.index') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Back</a></div>
    </div>

    @if ($status === 'draft' && $submitted)
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900"><strong>Menunggu verifikasi.</strong> Surat sudah dikirim oleh pembuat untuk diperiksa. Data terkunci sampai diverifikasi atau ditolak.</div>
    @elseif ($status === 'draft')
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"><strong>Draft.</strong> Surat masih dapat diperiksa dan diedit oleh pembuat sebelum Submit. Preview diberi watermark dan belum memiliki QR/TTE resmi.</div>
    @elseif ($status === 'validated')
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><strong>Sudah diverifikasi.</strong> Surat siap diterbitkan oleh penanda tangan. QR/TTE resmi belum aktif.</div>
    @elseif ($status === 'issued' && $letter->isExpired())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><strong>Masa berlaku berakhir.</strong> Dokumen tetap tersimpan dan dapat diverifikasi, tetapi masa berlakunya telah lewat.</div>
    @elseif ($status === 'issued')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><strong>Surat diterbitkan.</strong> Dokumen ini sudah resmi, memiliki QR/TTE verifikasi, dan tidak dapat diubah.</div>
    @elseif ($status === 'withdrawn')
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900"><strong>Surat ditarik.</strong> Dokumen tetap tersimpan untuk kebutuhan audit, tetapi tidak lagi berlaku.</div>
    @else
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900"><strong>Cancelled.</strong> Surat ini sudah dibatalkan dan tidak dapat diterbitkan kembali.</div>
    @endif

    @if ($withdrawalDecision)
        @if ($withdrawalDecision->status === \App\Enums\OutgoingLetterWithdrawalStatus::APPROVED)
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900"><div><strong>Penarikan disetujui.</strong> Diputuskan oleh {{ $withdrawalDecision->decidedBy?->name ?? 'Super Admin' }} pada {{ optional($withdrawalDecision->decided_at)->translatedFormat('d F Y H:i') }}.</div>@if($withdrawalDecision->decision_note)<div class="mt-1"><strong>Keterangan:</strong> {{ $withdrawalDecision->decision_note }}</div>@endif</div>
        @elseif ($withdrawalDecision->status === \App\Enums\OutgoingLetterWithdrawalStatus::REJECTED)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><div><strong>Pengajuan penarikan ditolak.</strong> Diputuskan oleh {{ $withdrawalDecision->decidedBy?->name ?? 'Super Admin' }} pada {{ optional($withdrawalDecision->decided_at)->translatedFormat('d F Y H:i') }}.</div><div class="mt-1"><strong>Alasan/keterangan:</strong> {{ $withdrawalDecision->decision_note }}</div></div>
        @endif
    @endif

    @if ($letter->rejection_reason)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-900"><strong>Alasan penolakan:</strong> {{ $letter->rejection_reason }} @if($letter->rejectedBy) <span class="text-red-700">· ditolak oleh {{ $letter->rejectedBy->name }}</span> @endif</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 sm:px-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">{{ $status === 'issued' ? 'Dokumen Resmi' : 'Preview Dokumen' }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Dirender dari template DOCX menggunakan LibreOffice.</p>
                    </div>
                    <a href="{{ route('outgoing-letters.pdf', $letter->id) }}" target="_blank" rel="noopener" class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Buka PDF</a>
                </div>
            </div>
            <div class="bg-slate-100 p-2 sm:p-4">
                <iframe
                    src="{{ route('outgoing-letters.pdf', $letter->id) }}"
                    title="{{ $status === 'issued' ? 'Dokumen resmi' : 'Preview PDF surat' }}"
                    class="h-[75vh] min-h-[620px] w-full rounded-xl bg-white shadow-sm"
                    loading="lazy"
                ></iframe>
            </div>
        </article>

        <aside class="h-fit space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Letter Details</h2><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-400">Number</dt><dd class="font-medium text-slate-800">{{ $letter->number }}</dd></div><div><dt class="text-slate-400">Recipient</dt><dd class="font-medium text-slate-800">{{ $letter->recipient_name }}</dd></div><div><dt class="text-slate-400">Address</dt><dd class="text-slate-700">{{ $letter->recipient_address ?: '—' }}</dd></div><div><dt class="text-slate-400">Letter Date</dt><dd class="font-medium text-slate-800">{{ optional($letter->letter_date)->translatedFormat('d F Y') ?? '—' }}</dd></div>@if($letter->letterType?->has_expiry && $letter->valid_from)<div><dt class="text-slate-400">Berlaku Mulai</dt><dd class="font-medium text-slate-800">{{ $letter->valid_from->translatedFormat('d F Y H:i') }}</dd></div>@endif @if($letter->letterType?->has_expiry && $letter->valid_until)<div><dt class="text-slate-400">Berlaku Sampai</dt><dd class="font-medium text-slate-800">{{ $letter->valid_until->translatedFormat('d F Y H:i') }}</dd></div>@endif @if($status === 'withdrawn' && $withdrawalDecision?->decided_at)<div><dt class="text-slate-400">Tanggal Penarikan</dt><dd class="font-medium text-red-700">{{ $withdrawalDecision->decided_at->translatedFormat('d F Y H:i') }}</dd></div>@endif @if($letter->validator_name)<div><dt class="text-slate-400">Verifikator</dt><dd class="font-medium text-slate-800">{{ $letter->validator_name }}</dd><dd class="text-xs text-slate-500">{{ $letter->validator_title }}</dd></div>@endif @if($letter->signer_name)<div><dt class="text-slate-400">Penanda Tangan</dt><dd class="font-medium text-slate-800">{{ $letter->signer_name }}</dd><dd class="text-xs text-slate-500">{{ $letter->signer_title }}</dd></div>@endif</dl></div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><h2 class="font-semibold text-slate-900">Riwayat Surat</h2><span class="text-xs text-slate-400">{{ $history->count() }} event</span></div><div class="mt-5 space-y-5">@forelse ($history as $event)<div class="relative pl-6">@if (!$loop->last)<span class="absolute left-[5px] top-3 h-full w-px bg-slate-200"></span>@endif<span class="absolute left-0 top-1.5 h-3 w-3 rounded-full border-2 border-white bg-slate-400 shadow"></span><div class="text-sm font-semibold text-slate-800">{{ ucfirst(str_replace('_', ' ', $event->action)) }}</div><div class="mt-0.5 text-xs text-slate-500">{{ $event->created_at?->translatedFormat('d F Y, H:i') }} · {{ $event->changedBy?->name ?? 'System' }}</div></div>@empty<p class="text-sm text-slate-500">Belum ada riwayat.</p>@endforelse</div></div>
            @if ($status === 'issued' && $verificationQrCode)<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center"><h2 class="font-semibold text-emerald-900">Dokumen Resmi</h2><div class="mt-4 flex justify-center"><img src="{{ $verificationQrCode }}" alt="QR verifikasi surat" class="h-36 w-36"></div><p class="mt-2 text-xs text-emerald-700">Scan QR untuk membuka halaman verifikasi.</p><a target="_blank" href="{{ route('verification.show', $letter->verification_token) }}" class="mt-4 inline-flex rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Open Verification</a></div>@endif
        </aside>
    </div>
</div>
