<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Surat — DANUM</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 px-4 py-10 text-slate-900">
    <main class="mx-auto max-w-xl">
        <div class="mb-6 text-center">
            <x-danum-logo class="mx-auto h-12 w-auto text-yellow-400" />
            <h1 class="mt-4 text-2xl font-semibold">Verifikasi Dokumen</h1>
            <p class="mt-1 text-sm text-slate-500">DANUM — Sistem Administrasi Surat</p>
        </div>

        @if ($letter)
            @php
                $state = $letter->status->value === 'withdrawn'
                    ? 'withdrawn'
                    : ($letter->isExpired() ? 'expired' : ($letter->isActive() ? 'active' : 'not_yet_active'));
                $withdrawal = $letter->withdrawalRequests->first(fn ($request) => $request->status->value !== 'pending');
            @endphp
            <section @class(['overflow-hidden rounded-2xl border bg-white shadow-sm','border-red-200' => $state === 'withdrawn','border-emerald-200' => $state === 'active','border-amber-200' => $state === 'expired' || $state === 'not_yet_active'])>
                <div @class(['border-b px-6 py-5','border-red-100 bg-red-50' => $state === 'withdrawn','border-emerald-100 bg-emerald-50' => $state === 'active','border-amber-100 bg-amber-50' => $state === 'expired' || $state === 'not_yet_active'])>
                    <div class="flex items-center gap-3">
                        <span @class(['flex h-10 w-10 items-center justify-center rounded-full text-lg font-bold','bg-red-100 text-red-700' => $state === 'withdrawn','bg-emerald-100 text-emerald-700' => $state === 'active','bg-amber-100 text-amber-700' => $state === 'expired' || $state === 'not_yet_active'])>{{ $state === 'withdrawn' ? '!' : '✓' }}</span>
                        <div>
                            <p class="text-sm font-bold">Dokumen Terverifikasi</p>
                            @if ($state === 'withdrawn')
                                <p class="mt-1 text-sm font-medium text-red-700">Surat ini telah ditarik dan tidak lagi berlaku.</p>
                            @elseif ($state === 'expired')
                                <p class="mt-1 text-sm font-medium text-amber-700">Surat ini telah melewati masa berlaku.</p>
                            @elseif ($state === 'not_yet_active')
                                <p class="mt-1 text-sm font-medium text-slate-600">Surat ini belum memasuki masa berlaku.</p>
                            @else
                                <p class="mt-1 text-sm font-medium text-emerald-700">Surat ini tercatat resmi dan masih berlaku.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <dl class="divide-y divide-slate-100 px-6">
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Nomor</dt><dd class="col-span-2 text-sm font-semibold">{{ $letter->number }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Jenis</dt><dd class="col-span-2 text-sm">{{ $letter->letterType?->name ?? '-' }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Instansi</dt><dd class="col-span-2 text-sm">{{ $letter->tenant?->name ?? '-' }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Penerima</dt><dd class="col-span-2 text-sm">{{ $letter->recipient_name }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Perihal</dt><dd class="col-span-2 text-sm">{{ $letter->subject }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Diterbitkan</dt><dd class="col-span-2 text-sm">{{ optional($letter->issued_at)->translatedFormat('d F Y') ?? '-' }}</dd></div>
                    @if($letter->letterType?->has_expiry)
                        <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Berlaku mulai</dt><dd class="col-span-2 text-sm">{{ optional($letter->valid_from)->translatedFormat('d F Y H:i') ?? '-' }}</dd></div>
                        <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Berlaku sampai</dt><dd class="col-span-2 text-sm">{{ optional($letter->valid_until)->translatedFormat('d F Y H:i') ?? '-' }}</dd></div>
                    @endif
                    @if($state === 'withdrawn' && $withdrawal?->decided_at)
                        <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Tanggal penarikan</dt><dd class="col-span-2 text-sm font-semibold text-red-700">{{ $withdrawal->decided_at->translatedFormat('d F Y H:i') }}</dd></div>
                        @if($withdrawal->decision_note)<div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Keterangan</dt><dd class="col-span-2 text-sm">{{ $withdrawal->decision_note }}</dd></div>@endif
                    @endif
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Status</dt><dd class="col-span-2 text-sm font-bold {{ $state === 'withdrawn' ? 'text-red-700' : ($state === 'active' ? 'text-emerald-700' : 'text-amber-700') }}">{{ match ($state) { 'active' => 'Aktif / Valid', 'expired' => 'Kedaluwarsa', 'withdrawn' => 'Ditarik', default => 'Belum Aktif' } }}</dd></div>
                </dl>
            </section>
        @else
            <section class="rounded-2xl border border-rose-200 bg-white p-8 text-center shadow-sm">
                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-600">!</div>
                <h2 class="font-semibold text-slate-900">Dokumen Tidak Terverifikasi</h2>
                <p class="mt-2 text-sm text-slate-500">Token verifikasi tidak ditemukan atau surat belum berstatus diterbitkan.</p>
            </section>
        @endif

        <p class="mt-6 text-center text-xs text-slate-400">Halaman verifikasi publik • DANUM</p>
    </main>
</body>
</html>
