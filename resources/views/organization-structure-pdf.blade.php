<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struktur Organisasi {{ $tenant->name }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 10px; }
        .header { text-align: center; border-bottom: 3px double #111827; padding-bottom: 10px; margin-bottom: 16px; }
        .header-title { font-size: 16px; font-weight: 700; text-transform: uppercase; }
        .header-subtitle { font-size: 12px; font-weight: 700; margin-top: 3px; text-transform: uppercase; }
        .org-title { text-align: center; font-size: 14px; font-weight: 700; text-transform: uppercase; margin: 12px 0 18px; }
        .tree { width: 100%; }
        .node { page-break-inside: avoid; margin-bottom: 8px; }
        .box { border: 1px solid #374151; border-radius: 7px; padding: 8px 10px; min-width: 160px; text-align: center; background: #fff; }
        .root .box { background: #dff3ff; border-color: #2563eb; }
        .name { font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .holder { margin-top: 4px; font-size: 9px; }
        .meta { margin-top: 2px; font-size: 8px; color: #4b5563; }
        .children { margin-left: 28px; border-left: 1px solid #9ca3af; padding-left: 18px; }
        .line { height: 8px; border-left: 1px solid #9ca3af; margin-left: 24px; }
        .type { font-size: 7px; color: #4b5563; text-transform: uppercase; margin-bottom: 3px; }
        .footer { margin-top: 26px; width: 100%; }
        .signature { width: 240px; margin-left: auto; text-align: center; }
        .signature-space { height: 48px; }
        .muted { color: #6b7280; font-size: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">{{ $tenant->city ?: 'PEMERINTAH DAERAH' }}</div>
        <div class="header-subtitle">{{ $tenant->district ? 'KECAMATAN ' . strtoupper($tenant->district) : '' }}</div>
        <div class="header-subtitle">{{ strtoupper($tenant->name) }}</div>
    </div>

    <div class="org-title">Struktur Organisasi<br>{{ strtoupper($tenant->name) }}</div>

    <div class="tree">
        @forelse($roots as $root)
            @include('organization-structure-pdf-node', ['node' => $root, 'nodes' => $nodes, 'depth' => 0])
        @empty
            <div class="muted" style="text-align:center;">Belum ada struktur organisasi yang ditetapkan.</div>
        @endforelse
    </div>

    <div class="footer">
        <div>Ditetapkan di : {{ $tenant->city ?: '........................' }}</div>
        <div>Tanggal : {{ $printedAt->translatedFormat('d F Y') }}</div>
        <div class="signature">
            <div>{{ $tenant->head_title ?: 'Kepala Organisasi' }}</div>
            <div class="signature-space"></div>
            <strong>{{ $tenant->head_name ?: '........................................' }}</strong>
        </div>
    </div>
</body>
</html>
