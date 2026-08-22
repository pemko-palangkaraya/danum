<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('outgoing-letters.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">← Outgoing Letters</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $letter->subject }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $letter->number }} · {{ $letter->letterType?->name }} · {{ $letter->status->value }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('outgoing-letters.pdf', $letter->id) }}" target="_blank" rel="noopener" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print / Save PDF</a>
            @if ($letter->status->value === 'issued')
                <a href="{{ route('outgoing-letters.pdf', ['id' => $letter->id, 'download' => 1]) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Download Issued PDF</a>
            @endif
            <a href="{{ route('outgoing-letters.index') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Back</a>
        </div>
    </div>

    @php
        $contentParts = preg_split('/(\{\{\s*tte\s*\}\})/i', (string) $letter->content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [(string) $letter->content];
    @endphp

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="mx-auto min-h-[297mm] max-w-[210mm] bg-white px-4 py-8 text-[14px] leading-7 text-slate-900">
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                    <strong>Preview surat.</strong> Format final mengikuti file DOCX master yang diupload Super Admin. Gunakan <strong>Print / Save PDF</strong> untuk melihat/menyimpan hasil dengan layout DOCX yang sebenarnya.
                </div>

                @if ($letter->tenant?->letterheadUrl())
                    <div class="mt-6 mb-7 border-b border-slate-300 pb-3"><img src="{{ $letter->tenant->letterheadUrl() }}" alt="Kop surat {{ $letter->tenant->name }}" class="mx-auto max-h-32 w-full object-contain"></div>
                @endif

                <section class="mb-7 text-center"><h2 class="text-base font-bold uppercase underline">{{ $letter->letterType?->name }}</h2><div class="text-sm">Nomor: {{ $letter->number }}</div></section>
                <dl class="mb-7 ml-4 grid grid-cols-[75px_1fr] gap-y-1 text-sm"><dt>Tujuan</dt><dd>: {{ $letter->recipient_name }}</dd>@if ($letter->recipient_address)<dt>Alamat</dt><dd>: {{ $letter->recipient_address }}</dd>@endif<dt>Perihal</dt><dd>: {{ $letter->subject }}</dd></dl>

                <div class="whitespace-pre-wrap font-serif">
                    @foreach ($contentParts as $part)
                        @if (preg_match('/^\{\{\s*tte\s*\}\}$/i', trim($part)))
                            @if ($letter->status->value === 'issued' && $verificationQrCode)
                                <div class="my-4 flex flex-col items-center text-center font-sans text-xs text-slate-500">
                                    <img src="{{ $verificationQrCode }}" alt="QR TTE / verifikasi surat" class="h-28 w-28">
                                    <span class="mt-1">TTE / verifikasi dokumen</span>
                                </div>
                            @else
                                <div class="my-4 flex justify-center font-sans text-xs text-slate-400">[ TTE / QR verifikasi ]</div>
                            @endif
                        @else
                            {!! nl2br(e($part)) !!}
                        @endif
                    @endforeach
                </div>
            </div>
        </article>

        <aside class="h-fit space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Letter Details</h2><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-400">Number</dt><dd class="font-medium text-slate-800">{{ $letter->number }}</dd></div><div><dt class="text-slate-400">Recipient</dt><dd class="font-medium text-slate-800">{{ $letter->recipient_name }}</dd></div><div><dt class="text-slate-400">Address</dt><dd class="text-slate-700">{{ $letter->recipient_address ?: '—' }}</dd></div><div><dt class="text-slate-400">Letter Date</dt><dd class="font-medium text-slate-800">{{ optional($letter->letter_date)->translatedFormat('d F Y') ?? '—' }}</dd></div><div><dt class="text-slate-400">Status</dt><dd class="font-medium capitalize text-slate-800">{{ $letter->status->value }}</dd></div></dl></div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><h2 class="font-semibold text-slate-900">Kop Surat</h2><p class="mt-1 text-sm text-slate-600">Tampilan surat mengikuti kop organisasi yang aktif.</p>@if ($letter->tenant?->letterheadUrl())<span class="mt-3 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Kop aktif</span>@else<span class="mt-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Kop default</span>@endif</div>
            @if ($letter->status->value === 'issued')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center"><h2 class="font-semibold text-emerald-900">Issued</h2><p class="mt-1 text-sm text-emerald-800">Surat sudah diterbitkan dan dapat diverifikasi publik.</p>@if ($verificationQrCode)<div class="mt-4 flex justify-center"><img src="{{ $verificationQrCode }}" alt="QR verifikasi surat" class="h-36 w-36"></div><p class="mt-2 text-xs text-emerald-700">Scan QR untuk membuka halaman verifikasi.</p>@endif<a target="_blank" href="{{ route('verification.show', $letter->verification_token) }}" class="mt-4 inline-flex rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Open Verification</a></div>
            @endif
        </aside>
    </div>
</div>
