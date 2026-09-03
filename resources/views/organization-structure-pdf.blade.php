<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struktur Organisasi {{ $tenant->name }}</title>
    <style>
        @page { margin: 28px 32px 30px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 9px;
            margin: 0;
        }
        .header {
            position: relative;
            text-align: center;
            border-bottom: 3px double #1f2937;
            padding: 0 70px 11px;
            margin-bottom: 18px;
            min-height: 54px;
        }
        .header-logo {
            position: absolute;
            left: 4px;
            top: -2px;
            width: 48px;
            height: 56px;
            object-fit: contain;
        }
        .header-title {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .header-subtitle {
            font-size: 10px;
            font-weight: 700;
            margin-top: 3px;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .org-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.35;
            margin: 0 0 20px;
        }
        .tree {
            width: 100%;
            text-align: center;
        }
        .tree-root {
            width: 54%;
            margin: 0 auto;
        }
        .box {
            border: 1px solid #2563eb;
            border-radius: 7px;
            padding: 8px 9px;
            min-height: 48px;
            text-align: center;
            background: #eff8ff;
            vertical-align: middle;
        }
        .root-box {
            background: #e0f2fe;
            border-width: 1.2px;
            padding-top: 9px;
            padding-bottom: 9px;
        }
        .type {
            font-size: 7px;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 3px;
            line-height: 1.15;
        }
        .name {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.35;
        }
        .holder {
            margin-top: 4px;
            font-size: 8px;
            line-height: 1.25;
        }
        .meta {
            margin-top: 3px;
            font-size: 7px;
            color: #64748b;
            line-height: 1.2;
        }
        .down-line {
            width: 1px;
            height: 13px;
            margin: 0 auto;
            background: #94a3b8;
        }
        .children-row {
            display: table;
            width: 100%;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 8px 0;
            margin-top: 0;
        }
        .children-cell {
            display: table-cell;
            width: auto;
            vertical-align: top;
            padding-top: 10px;
            position: relative;
        }
        .children-cell:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #94a3b8;
        }
        .children-cell:after {
            content: '';
            position: absolute;
            top: 0;
            left: 50%;
            height: 10px;
            border-left: 1px solid #94a3b8;
        }
        .nested {
            margin-top: 0;
        }
        .empty {
            color: #64748b;
            font-size: 9px;
            padding: 30px;
        }
        .footer {
            margin-top: 32px;
            width: 100%;
            display: table;
        }
        .footer-left,
        .signature {
            display: table-cell;
            vertical-align: top;
        }
        .footer-left {
            width: 62%;
            font-size: 8px;
            line-height: 1.6;
        }
        .signature {
            width: 38%;
            text-align: center;
            font-size: 8px;
        }
        .signature-space {
            height: 42px;
        }
        .signature-line {
            border-top: 1px dotted #111827;
            width: 72%;
            margin: 0 auto 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($tenant->logo))
            <img class="header-logo" src="{{ $tenant->logo }}" alt="Logo">
        @endif
        <div class="header-title">{{ $tenant->city ?: 'PEMERINTAH DAERAH' }}</div>
        @if($tenant->district)
            <div class="header-subtitle">KECAMATAN {{ strtoupper($tenant->district) }}</div>
        @endif
        <div class="header-subtitle">{{ strtoupper($tenant->name) }}</div>
    </div>

    <div class="org-title">STRUKTUR ORGANISASI<br>{{ strtoupper($tenant->name) }}</div>

    <div class="tree">
        @forelse($roots as $root)
            <div class="tree-root">
                @include('organization-structure-pdf-node', ['node' => $root, 'nodes' => $nodes, 'depth' => 0, 'root' => true])
            </div>
        @empty
            <div class="empty">Belum ada struktur organisasi yang ditetapkan.</div>
        @endforelse
    </div>

    <div class="footer">
        <div class="footer-left">
            <div>Ditetapkan di : {{ $tenant->city ?: '........................' }}</div>
            <div>Tanggal&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $printedAt->translatedFormat('d F Y') }}</div>
        </div>
        <div class="signature">
            <div>{{ $tenant->head_title ?: 'Kepala Organisasi' }}</div>
            <div class="signature-space"></div>
            <div class="signature-line"></div>
            <strong>{{ $tenant->head_name ?: '........................................' }}</strong>
        </div>
    </div>
</body>
</html>
