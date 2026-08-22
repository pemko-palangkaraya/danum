<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('outgoing-letters.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">← Outgoing Letters</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $letter->subject }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $letter->number }} · {{ $letter->letterType?->name }} · {{ $letter->status->value }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print / Save PDF</button>
            @if ($letter->status->value === 'issued')
                <a href="{{ route('outgoing-letters.pdf', $letter->id) }}" class="rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Download Issued PDF</a>
            @endif
            <a href="{{ route('outgoing-letters.index') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm print:border-0 print:p-0 print:shadow-none sm:p-8">
            <div class="mx-auto min-h-[297mm] max-w-[210mm] bg-white px-4 py-8 text-[14px] leading-7 text-slate-900 print:min-h-0 print:max-w-none print:px-0 print:py-0">
                @if ($letter->tenant?->letterheadUrl())
                    <div class="mb-7 border-b border-slate-300 pb-3"><img src="{{ $letter->tenant->letterheadUrl() }}" alt="Kop surat {{ $letter->tenant->name }}" class="mx-auto max-h-32 w-full object-contain"></div>
                @else
                    <header class="mb-7 border-b-4 border-slate-900 pb-3 text-center"><div class="text-lg font-bold uppercase tracking-tight">{{ $letter->tenant?->name }}</div><div class="text-xs leading-5 text-slate-500">{{ implode(', ', array_filter([$letter->tenant?->address, $letter->tenant?->village, $letter->tenant?->district, $letter->tenant?->city, $letter->tenant?->province])) }} @if ($letter->tenant?->phone) · {{ $letter->tenant->phone }} @endif</div></header>
                @endif

                <section class="mb-7 text-center"><h2 class="text-base font-bold uppercase underline">{{ $letter->letterType?->name }}</h2><div class="text-sm">Nomor: {{ $letter->number }}</div></section>
                <dl class="mb-7 ml-4 grid grid-cols-[75px_1fr] gap-y-1 text-sm"><dt>Tujuan</dt><dd>: {{ $letter->recipient_name }}</dd>@if ($letter->recipient_address)<dt>Alamat</dt><dd>: {{ $letter->recipient_address }}</dd>@endif<dt>Perihal</dt><dd>: {{ $letter->subject }}</dd></dl>
                <div class="whitespace-pre-wrap font-serif">{{ $letter->content }}</div>

                <div class="ml-auto mt-12 w-2/5 text-center text-sm">
                    <div>{{ $letter->tenant?->head_title ?? 'Pimpinan' }}</div>
                    <div class="h-20"></div>
                    <strong>{{ $letter->tenant?->head_name ?? '-' }}</strong>
                </div>

                @if ($letter->status->value === 'issued' && $letter->verification_token)
                    <div class="mt-8 border-t border-slate-200 pt-3 text-center text-[10px] leading-4 text-slate-500">
                        <div class="font-semibold text-slate-700">Dokumen diterbitkan · scan QR untuk verifikasi</div>
                        @if ($verificationQrCode)<div class="mt-3 flex justify-center"><img src="{{ $verificationQrCode }}" alt="QR verifikasi surat" class="h-28 w-28"></div>@endif
                        <div class="mt-2 break-all">{{ route('verification.show', ['token' => $letter->verification_token]) }}</div>
                    </div>
                @endif
            </div>
        </article>

        <aside class="h-fit space-y-4 print:hidden">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-semibold text-slate-900">Letter Details</h2><dl class="mt-4 space-y-3 text-sm"><div><dt class="text-slate-400">Number</dt><dd class="font-medium text-slate-800">{{ $letter->number }}</dd></div><div><dt class="text-slate-400">Recipient</dt><dd class="font-medium text-slate-800">{{ $letter->recipient_name }}</dd></div><div><dt class="text-slate-400">Address</dt><dd class="text-slate-700">{{ $letter->recipient_address ?: '—' }}</dd></div><div><dt class="text-slate-400">Template Version</dt><dd class="font-medium text-slate-800">v{{ $letter->letterTypeVersion?->version ?? '—' }}</dd></div><div><dt class="text-slate-400">Letter Date</dt><dd class="font-medium text-slate-800">{{ optional($letter->letter_date)->translatedFormat('d F Y') ?? '—' }}</dd></div><div><dt class="text-slate-400">Status</dt><dd class="font-medium capitalize text-slate-800">{{ $letter->status->value }}</dd></div></dl></div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5"><h2 class="font-semibold text-slate-900">Kop Surat</h2><p class="mt-1 text-sm text-slate-600">Tampilan surat mengikuti kop organisasi yang aktif.</p>@if ($letter->tenant?->letterheadUrl())<span class="mt-3 inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Kop aktif</span>@else<span class="mt-3 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Kop default</span>@endif</div>
            @if ($letter->status->value === 'issued')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center"><h2 class="font-semibold text-emerald-900">Issued</h2><p class="mt-1 text-sm text-emerald-800">Surat sudah diterbitkan dan dapat diverifikasi publik.</p>@if ($verificationQrCode)<div class="mt-4 flex justify-center"><img src="{{ $verificationQrCode }}" alt="QR verifikasi surat" class="h-36 w-36"></div><p class="mt-2 text-xs text-emerald-700">Scan QR untuk membuka halaman verifikasi.</p>@endif<a target="_blank" href="{{ route('verification.show', $letter->verification_token) }}" class="mt-4 inline-flex rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Open Verification</a></div>
            @endif
        </aside>
    </div>
</div>
