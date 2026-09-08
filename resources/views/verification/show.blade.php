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
            <p class="mt-1 text-sm text-slate-500">Data Administrasi dan Urusan Masyarakat</p>
        </div>

        @php
            $state = $letter->status->value === 'withdrawn'
                ? 'withdrawn'
                : ($letter->isExpired() ? 'expired' : ($letter->isActive() ? 'active' : 'not_yet_active'));
            $withdrawal = $letter->withdrawalRequests->first(fn ($request) => $request->status->value !== 'pending');
            $isRestricted = $accessLevel->value === 'restricted';
            $isProtected = $accessLevel->value === 'protected';
            $tteStatus = $tte['status'];
            $tteValid = $tteStatus === 'valid';
        @endphp

        <section @class([
            'overflow-hidden rounded-2xl border bg-white shadow-sm',
            'border-red-200' => $state === 'withdrawn',
            'border-emerald-200' => $state === 'active',
            'border-amber-200' => $state === 'expired' || $state === 'not_yet_active',
        ])>
            <div @class([
                'border-b px-6 py-5',
                'border-red-100 bg-red-50' => $state === 'withdrawn',
                'border-emerald-100 bg-emerald-50' => $state === 'active',
                'border-amber-100 bg-amber-50' => $state === 'expired' || $state === 'not_yet_active',
            ])>
                <div class="flex items-center gap-3">
                    <span @class([
                        'flex h-10 w-10 items-center justify-center rounded-full text-lg font-bold',
                        'bg-red-100 text-red-700' => $state === 'withdrawn',
                        'bg-emerald-100 text-emerald-700' => $state === 'active',
                        'bg-amber-100 text-amber-700' => $state === 'expired' || $state === 'not_yet_active',
                    ])>{{ $state === 'withdrawn' ? '!' : '✓' }}</span>
                    <div>
                        <p class="text-sm font-bold">
                            {{ $isRestricted ? 'Dokumen Terdaftar' : ($isProtected ? 'Tanda Tangan Elektronik' : 'Dokumen Terverifikasi') }}
                        </p>
                        @if ($state === 'withdrawn')
                            <p class="mt-1 text-sm font-medium text-red-700">Surat ini telah ditarik dan tidak lagi berlaku.</p>
                        @elseif ($state === 'expired')
                            <p class="mt-1 text-sm font-medium text-amber-700">Surat ini telah melewati masa berlaku.</p>
                        @elseif ($state === 'not_yet_active')
                            <p class="mt-1 text-sm font-medium text-slate-600">Surat ini belum memasuki masa berlaku.</p>
                        @elseif ($isRestricted)
                            <p class="mt-1 text-sm font-medium text-slate-600">Isi dokumen tidak tersedia melalui layanan verifikasi publik.</p>
                        @elseif ($tteValid)
                            <p class="mt-1 text-sm font-medium text-emerald-700">Tanda tangan PAdES terverifikasi secara kriptografis.</p>
                        @elseif ($tteStatus === 'unsigned')
                            <p class="mt-1 text-sm font-medium text-amber-700">Dokumen belum memiliki tanda tangan elektronik.</p>
                        @else
                            <p class="mt-1 text-sm font-medium text-red-700">Tanda tangan elektronik tidak dapat diverifikasi.</p>
                        @endif
                    </div>
                </div>
            </div>

            <dl class="divide-y divide-slate-100 px-6">
                <div class="grid grid-cols-3 gap-4 py-4">
                    <dt class="text-sm text-slate-500">Nomor</dt>
                    <dd class="col-span-2 text-sm font-semibold">{{ $letter->number }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 py-4">
                    <dt class="text-sm text-slate-500">Tanggal</dt>
                    <dd class="col-span-2 text-sm">{{ optional($letter->issued_at)->translatedFormat('d F Y') ?? '-' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 py-4">
                    <dt class="text-sm text-slate-500">Jenis</dt>
                    <dd class="col-span-2 text-sm">{{ $letter->letterType?->name ?? '-' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 py-4">
                    <dt class="text-sm text-slate-500">Instansi</dt>
                    <dd class="col-span-2 text-sm">{{ $letter->tenant?->name ?? '-' }}</dd>
                </div>
                <div class="grid grid-cols-3 gap-4 py-4">
                    <dt class="text-sm text-slate-500">Penandatangan</dt>
                    <dd class="col-span-2 text-sm font-semibold">{{ $letter->signer_name ?? '-' }}</dd>
                </div>
                @if(filled($letter->signer_title))
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">Jabatan</dt>
                        <dd class="col-span-2 text-sm">{{ $letter->signer_title }}</dd>
                    </div>
                @endif
                <div class="grid grid-cols-3 gap-4 py-4">
                    <dt class="text-sm text-slate-500">Status TTE</dt>
                    <dd class="col-span-2 text-sm font-bold {{ $tteValid ? 'text-emerald-700' : ($tteStatus === 'unsigned' ? 'text-amber-700' : 'text-red-700') }}">
                        {{ match ($tteStatus) {
                            'valid' => 'Valid — PAdES B-T',
                            'unsigned' => 'Tidak ditandatangani secara elektronik',
                            default => 'Tidak valid / gagal diverifikasi',
                        } }}
                    </dd>
                </div>
                @if($tte['message'])
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">Verifikasi TTE</dt>
                        <dd class="col-span-2 text-sm">{{ $tte['message'] }}</dd>
                    </div>
                @endif
                @if($tteValid && $letter->signed_at)
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">Waktu TTE</dt>
                        <dd class="col-span-2 text-sm">{{ $letter->signed_at->translatedFormat('d F Y H:i:s') }}</dd>
                    </div>
                @endif
                @if($isRestricted || $isProtected)
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">ID Dokumen</dt>
                        <dd class="col-span-2 break-all text-xs font-mono text-slate-600">{{ $letter->id }}</dd>
                    </div>
                @endif
                @if($accessLevel->value === 'public')
                    @if(filled($letter->subject))
                        <div class="grid grid-cols-3 gap-4 py-4">
                            <dt class="text-sm text-slate-500">Perihal</dt>
                            <dd class="col-span-2 text-sm">{{ $letter->subject }}</dd>
                        </div>
                    @endif
                    @if(filled($letter->recipient_name))
                        <div class="grid grid-cols-3 gap-4 py-4">
                            <dt class="text-sm text-slate-500">Penerima</dt>
                            <dd class="col-span-2 text-sm">{{ $letter->recipient_name }}</dd>
                        </div>
                    @endif
                @endif
                @if($letter->document_hash)
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">Hash Dokumen</dt>
                        <dd class="col-span-2 break-all text-xs font-mono text-slate-600">{{ $letter->document_hash }}</dd>
                    </div>
                @endif
                @if($letter->letterType?->has_expiry)
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">Berlaku</dt>
                        <dd class="col-span-2 text-sm">
                            {{ optional($letter->valid_from)->translatedFormat('d F Y') ?? '-' }}
                            —
                            {{ optional($letter->valid_until)->translatedFormat('d F Y') ?? '-' }}
                        </dd>
                    </div>
                @endif
                @if($state === 'withdrawn' && $withdrawal?->decided_at)
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <dt class="text-sm text-slate-500">Tanggal penarikan</dt>
                        <dd class="col-span-2 text-sm font-semibold text-red-700">{{ $withdrawal->decided_at->translatedFormat('d F Y H:i') }}</dd>
                    </div>
                    @if($withdrawal->decision_note)
                        <div class="grid grid-cols-3 gap-4 py-4">
                            <dt class="text-sm text-slate-500">Keterangan</dt>
                            <dd class="col-span-2 text-sm">{{ $withdrawal->decision_note }}</dd>
                        </div>
                    @endif
                @endif
                <div class="grid grid-cols-3 gap-4 py-4">
                    <dt class="text-sm text-slate-500">Status</dt>
                    <dd class="col-span-2 text-sm font-bold {{ $state === 'withdrawn' ? 'text-red-700' : ($state === 'active' ? 'text-emerald-700' : 'text-amber-700') }}">{{ match ($state) { 'active' => 'Aktif / Valid', 'expired' => 'Kedaluwarsa', 'withdrawn' => 'Ditarik', default => 'Belum Aktif' } }}</dd>
                </div>
            </dl>

            @if($accessLevel->value === 'public' && filled($letter->signed_pdf_path ?: $letter->unsigned_pdf_path))
                <div class="border-t border-slate-100 px-6 py-5">
                    <a href="{{ route('verification.document', $letter->verification_token) }}" class="flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Lihat Dokumen</a>
                </div>
            @elseif($isProtected)
                <div class="border-t border-slate-100 px-6 py-5">
                    <p class="mb-3 text-center text-sm text-slate-500">Dokumen ini memerlukan autentikasi untuk mengakses isi dokumen.</p>
                    <a href="{{ route('verification.document', $letter->verification_token) }}" class="flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Login untuk mengakses</a>
                </div>
            @endif
        </section>

        <p class="mt-6 text-center text-xs text-slate-400">Halaman verifikasi publik • DANUM</p>
    </main>
</body>

</html>
