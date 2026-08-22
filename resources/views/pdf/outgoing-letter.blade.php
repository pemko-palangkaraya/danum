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
        .content { white-space: pre-wrap; text-align: left; }
        .content-part { white-space: pre-wrap; }
        .tte-placeholder { margin: 10px 0; text-align: center; color: #6b7280; font-size: 9pt; }
        .verification-qr { margin: 7px auto 4px; width: 30mm; height: 30mm; }
        .verification-qr img { width: 30mm; height: 30mm; }
        .verification-url { margin-top: 4px; font-size: 7pt; color: #6b7280; word-break: break-all; }
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
        $contentParts = preg_split('/(\{\{\s*tte\s*\}\})/i', (string) $letter->content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [(string) $letter->content];
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

    <main class="content">
        @foreach ($contentParts as $part)
            @if (preg_match('/^\{\{\s*tte\s*\}\}$/i', trim($part)))
                @if ($letter->status->value === 'issued' && $letter->verification_token && $verificationQrCode)
                    <div class="tte-placeholder">
                        <div class="verification-qr">
                            <img src="{{ $verificationQrCode }}" alt="QR TTE / verifikasi surat">
                        </div>
                        <div>Dokumen diterbitkan dan dapat diverifikasi secara publik.</div>
                        <div class="verification-url">{{ route('verification.show', ['token' => $letter->verification_token]) }}</div>
                    </div>
                @else
                    <div class="tte-placeholder">TTE / QR verifikasi akan ditempatkan di sini saat surat diterbitkan.</div>
                @endif
            @else
                <div class="content-part">{{ $part }}</div>
            @endif
        @endforeach
    </main>
</body>
</html>
