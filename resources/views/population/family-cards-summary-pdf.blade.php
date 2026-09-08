<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Kartu Keluarga - {{ $tenant->name }}</title>
    <style>
        @page { margin: 24px 28px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; }
        .header { text-align: center; margin: 22px 0 18px; }
        .title { font-size: 19px; font-weight: bold; }
        .tenant { font-size: 13px; font-weight: bold; margin-top: 6px; }
        .meta { font-size: 8px; margin-top: 5px; color: #555; line-height: 1.5; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 7px; margin-bottom: 12px; }
        .summary td { width: 25%; border: 1px solid #222; padding: 13px 8px; text-align: center; }
        .number { display: block; font-size: 19px; font-weight: bold; }
        .label { display: block; margin-top: 4px; font-size: 8px; font-weight: bold; }
        .breakdown { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .breakdown th, .breakdown td { border: 1px solid #222; padding: 7px; }
        .breakdown th { text-align: left; background: #f1f1f1; }
        .breakdown td:last-child { text-align: right; font-weight: bold; }
        .note { margin-top: 18px; font-size: 7px; color: #555; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">REKAPITULASI DATA KARTU KELUARGA</div>
        <div class="tenant">{{ $tenant->name }}</div>
        <div class="meta">
            {{ $tenant->village ?: '-' }}, {{ $tenant->district ?: '-' }}, {{ $tenant->city ?: '-' }}, {{ $tenant->province ?: '-' }}
            <br>
            Dicetak {{ $printedAt->format('d-m-Y H:i') }} WIB
        </div>
    </div>

    <table class="summary">
        <tr>
            <td><span class="number">{{ number_format($aggregate['total_families']) }}</span><span class="label">Total KK</span></td>
            <td><span class="number">{{ number_format($aggregate['total_members']) }}</span><span class="label">Total Anggota KK</span></td>
            <td><span class="number">{{ number_format($aggregate['male']) }}</span><span class="label">Laki-laki</span></td>
            <td><span class="number">{{ number_format($aggregate['female']) }}</span><span class="label">Perempuan</span></td>
        </tr>
        <tr>
            <td><span class="number">{{ number_format($aggregate['wni']) }}</span><span class="label">WNI</span></td>
            <td><span class="number">{{ number_format($aggregate['wna']) }}</span><span class="label">WNA</span></td>
            <td><span class="number">{{ number_format($aggregate['average_members'], 2) }}</span><span class="label">Rata-rata Anggota/KK</span></td>
            <td><span class="number">{{ number_format($aggregate['active_families']) }}</span><span class="label">KK Aktif</span></td>
        </tr>
    </table>

    <table class="breakdown">
        <thead>
            <tr>
                <th>Indikator</th>
                <th>Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>Jumlah Kartu Keluarga</td><td>{{ number_format($aggregate['total_families']) }}</td></tr>
            <tr><td>Jumlah KK aktif</td><td>{{ number_format($aggregate['active_families']) }}</td></tr>
            <tr><td>Jumlah anggota keluarga aktif</td><td>{{ number_format($aggregate['total_members']) }}</td></tr>
            <tr><td>Anggota laki-laki</td><td>{{ number_format($aggregate['male']) }}</td></tr>
            <tr><td>Anggota perempuan</td><td>{{ number_format($aggregate['female']) }}</td></tr>
            <tr><td>Warga Negara Indonesia</td><td>{{ number_format($aggregate['wni']) }}</td></tr>
            <tr><td>Warga Negara Asing</td><td>{{ number_format($aggregate['wna']) }}</td></tr>
        </tbody>
    </table>

    <div class="note">
        Halaman ini merupakan halaman agregat seluruh Kartu Keluarga pada tenant yang dicetak. Halaman berikutnya berisi Kartu Keluarga satu per satu sesuai urutan nomor KK.
    </div>
</body>
</html>
