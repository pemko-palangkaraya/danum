<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24mm 22mm 22mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11pt; line-height: 1.55; }
        .header { border-bottom: 3px solid #111827; padding-bottom: 12px; text-align: center; }
        .tenant-name { font-size: 16pt; font-weight: bold; text-transform: uppercase; }
        .tenant-address { font-size: 9pt; }
        .letter-number { margin: 24px 0 22px; text-align: center; }
        .letter-number h1 { font-size: 14pt; margin: 0 0 4px; text-decoration: underline; }
        .meta { margin: 0 0 18px 36px; }
        .meta td:first-child { width: 95px; vertical-align: top; }
        .content { white-space: pre-line; text-align: justify; }
        .signature { margin-left: 55%; margin-top: 40px; text-align: center; }
        .signature-space { height: 64px; }
        .verification { margin-top: 28px; border-top: 1px solid #d1d5db; padding-top: 8px; text-align: center; font-size: 7pt; color: #6b7280; }
        .verification strong { color: #111827; }
    </style>
</head>
<body>
    <header class="header">
        <div class="tenant-name">{{ $letter->tenant->name }}</div>
        <div class="tenant-address">
            {{ implode(', ', array_filter([$letter->tenant->village, $letter->tenant->district, $letter->tenant->city, $letter->tenant->province])) }}
            @if ($letter->tenant->phone) | Tel. {{ $letter->tenant->phone }} @endif
        </div>
    </header>
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
        <div>{{ $letter->tenant->city }}, {{ optional($letter->issued_at)->translatedFormat('d F Y') ?? '-' }}</div>
        <div>{{ $letter->tenant->head_title ?? 'Pimpinan' }}</div>
        <div class="signature-space"></div>
        <strong>{{ $letter->tenant->head_name ?? '-' }}</strong>
    </section>
    @if ($letter->status->value === 'issued' && $letter->verification_token)
        <div class="verification"><strong>Verifikasi dokumen:</strong><br>{{ route('verification.show', ['token' => $letter->verification_token]) }}</div>
    @endif
</body>
</html>
