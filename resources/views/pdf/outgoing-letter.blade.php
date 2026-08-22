<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 18mm 20mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11pt; line-height: 1.55; }
        .letterhead-image { width: 100%; max-height: 42mm; object-fit: contain; margin-bottom: 8px; }
        .generated-head { border-bottom: 3px solid #111827; padding: 0 0 10px; text-align: center; }
        .generated-head-inner { display: table; width: 100%; }
        .logo-cell { display: table-cell; width: 22%; vertical-align: middle; text-align: left; }
        .logo { max-width: 28mm; max-height: 25mm; }
        .tenant-cell { display: table-cell; width: 78%; vertical-align: middle; text-align: center; }
        .tenant-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
        .tenant-address { font-size: 9pt; }
        .letter-number { margin: 22px 0 20px; text-align: center; }
        .letter-number h1 { font-size: 14pt; margin: 0 0 4px; text-decoration: underline; }
        .meta { margin: 0 0 18px 30px; }
        .meta td:first-child { width: 75px; vertical-align: top; }
        .content { white-space: pre-line; text-align: justify; }
        .signature { margin-left: 55%; margin-top: 36px; text-align: center; }
        .signature-space { height: 60px; }
        .verification { margin-top: 24px; border-top: 1px solid #d1d5db; padding-top: 8px; text-align: center; font-size: 7pt; color: #6b7280; }
        .verification strong { color: #111827; }
        .verification-qr { margin: 7px auto 4px; width: 27mm; height: 27mm; }
        .verification-qr img { width: 27mm; height: 27mm; }
    </style>
</head>
<body>
    @php
        $letterheadFile = $letter->tenant->letterhead_path
            ? storage_path('app/public/' . $letter->tenant->letterhead_path)
            : null;
        $letterheadMime = $letterheadFile && is_file($letterheadFile) ? mime_content_type($letterheadFile) : null;
        $letterheadData = $letterheadFile && is_file($letterheadFile)
            ? 'data:' . $letterheadMime . ';base64,' . base64_encode(file_get_contents($letterheadFile))
            : null;
        $logoFile = $letter->tenant->logo && !str_starts_with($letter->tenant->logo, 'http')
            ? public_path('storage/' . ltrim($letter->tenant->logo, '/'))
            : null;
        $logoMime = $logoFile && is_file($logoFile) ? mime_content_type($logoFile) : null;
        $logoData = $logoFile && is_file($logoFile)
            ? 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoFile))
            : null;
    @endphp

    @if ($letterheadData)
        <img class="letterhead-image" src="{{ $letterheadData }}" alt="Kop surat">
    @else
        <header class="generated-head">
            <div class="generated-head-inner">
                @if ($logoData)
                    <div class="logo-cell"><img class="logo" src="{{ $logoData }}" alt="Logo"></div>
                @endif
                <div class="tenant-cell">
                    <div class="tenant-name">{{ $letter->tenant->name }}</div>
                    <div class="tenant-address">
                        {{ implode(', ', array_filter([$letter->tenant->address, $letter->tenant->village, $letter->tenant->district, $letter->tenant->city, $letter->tenant->province])) }}
                        @if ($letter->tenant->phone) | Tel. {{ $letter->tenant->phone }} @endif
                        @if ($letter->tenant->email) | {{ $letter->tenant->email }} @endif
                    </div>
                </div>
            </div>
        </header>
    @endif

    <section class="letter-number">
        <h1>{{ $letter->letterType->name }}</h1>
        <div>Nomor: {{ $letter->number }}</div>
    </section>
    <table class="meta">
        <tr><td>Tujuan</td><td>: {{ $letter->recipient_name }}</td></tr>
        @if ($letter->recipient_address)<tr><td>Alamat</td><td>: {{ $letter->recipient_address }}</td></tr>@endif
        <tr><td>Perihal</td><td>: {{ $letter->subject }}</td></tr>
    </table>
    <main class="content">{{ $letter->content }}</main>
    <section class="signature">
        <div>{{ $letter->tenant->head_title ?? 'Pimpinan' }}</div>
        <div class="signature-space"></div>
        <strong>{{ $letter->tenant->head_name ?? '-' }}</strong>
    </section>
    @if ($letter->status->value === 'issued' && $letter->verification_token)
        <div class="verification">
            <strong>Verifikasi dokumen — scan QR</strong>
            @if ($verificationQrCode)
                <div class="verification-qr">
                    <img src="{{ $verificationQrCode }}" alt="QR verifikasi surat">
                </div>
            @endif
            <div>{{ route('verification.show', ['token' => $letter->verification_token]) }}</div>
        </div>
    @endif
</body>
</html>
