<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('outgoing-letters.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-900">← Outgoing Letters</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $letter->subject }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $letter->number }} · {{ $letter->letterType?->name }} · {{ $letter->status->value }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="window.print()" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Print / Save PDF</button>
            <a href="{{ route('outgoing-letters.index') }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Back</a>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
        <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm print:border-0 print:p-0 print:shadow-none">
            <div class="mx-auto min-h-[297mm] max-w-[210mm] bg-white px-10 py-12 text-[14px] leading-7 text-slate-900 print:min-h-0 print:max-w-none print:px-0 print:py-0">
                <div class="whitespace-pre-wrap font-serif">{{ $letter->content }}</div>
            </div>
        </article>

        <aside class="h-fit space-y-4 print:hidden">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-semibold text-slate-900">Letter Details</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-slate-400">Number</dt><dd class="font-medium text-slate-800">{{ $letter->number }}</dd></div>
                    <div><dt class="text-slate-400">Recipient</dt><dd class="font-medium text-slate-800">{{ $letter->recipient_name }}</dd></div>
                    <div><dt class="text-slate-400">Address</dt><dd class="text-slate-700">{{ $letter->recipient_address ?: '—' }}</dd></div>
                    <div><dt class="text-slate-400">Template Version</dt><dd class="font-medium text-slate-800">v{{ $letter->letterTypeVersion?->version ?? '—' }}</dd></div>
                    <div><dt class="text-slate-400">Status</dt><dd class="font-medium capitalize text-slate-800">{{ $letter->status->value }}</dd></div>
                </dl>
            </div>

            @if ($letter->status->value === 'issued')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <h2 class="font-semibold text-emerald-900">Issued</h2>
                    <p class="mt-1 text-sm text-emerald-800">Surat sudah diterbitkan dan dapat diverifikasi publik.</p>
                    <a target="_blank" href="{{ route('verification.show', $letter->verification_token) }}" class="mt-4 inline-flex rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-800">Open Verification</a>
                </div>
            @endif
        </aside>
    </div>
</div>
