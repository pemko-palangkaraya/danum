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
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 text-white">✓</div>
            <h1 class="text-2xl font-semibold">Verifikasi Dokumen</h1>
            <p class="mt-1 text-sm text-slate-500">DANUM — Sistem Administrasi Surat</p>
        </div>

        @if ($letter)
            <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm">
                <div class="border-b border-emerald-100 bg-emerald-50 px-6 py-5">
                    <p class="text-sm font-semibold text-emerald-700">✓ Dokumen Terverifikasi</p>
                    <p class="mt-1 text-sm text-emerald-700/80">Surat ini tercatat sebagai dokumen yang diterbitkan secara resmi.</p>
                </div>
                <dl class="divide-y divide-slate-100 px-6">
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Nomor</dt><dd class="col-span-2 text-sm font-semibold">{{ $letter->number }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Jenis</dt><dd class="col-span-2 text-sm">{{ $letter->letterType?->name ?? '-' }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Instansi</dt><dd class="col-span-2 text-sm">{{ $letter->tenant?->name ?? '-' }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Penerima</dt><dd class="col-span-2 text-sm">{{ $letter->recipient_name }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Perihal</dt><dd class="col-span-2 text-sm">{{ $letter->subject }}</dd></div>
                    <div class="grid grid-cols-3 gap-4 py-4"><dt class="text-sm text-slate-500">Diterbitkan</dt><dd class="col-span-2 text-sm">{{ optional($letter->issued_at)->translatedFormat('d F Y') ?? '-' }}</dd></div>
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
