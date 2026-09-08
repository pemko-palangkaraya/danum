<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Ringkasan Data Kartu Keluarga - {{ $tenant->name }}</title>
    <style>
        @page {
            margin: 14px 18px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 6.5px;
            color: #111;
            margin: 0;
        }

        .header {
            border-bottom: 1.5px solid #111;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }

        .title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 8px;
            font-weight: bold;
            margin-top: 2px;
        }

        .meta {
            font-size: 6px;
            color: #555;
            margin-top: 2px;
        }

        .scope {
            float: right;
            text-align: right;
            font-size: 6px;
            line-height: 1.35;
        }

        .clearfix {
            clear: both;
        }

        .kpi {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            margin: 0 -3px 4px;
        }

        .kpi td {
            width: 16.666%;
            border: 1px solid #222;
            padding: 5px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .kpi .value {
            display: block;
            font-size: 11px;
            font-weight: bold;
        }

        .kpi .label {
            display: block;
            margin-top: 2px;
            font-size: 5.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px 0;
            margin: 0 -4px;
            table-layout: fixed;
        }

        .grid>tbody>tr>td {
            width: 33.333%;
            vertical-align: top;
            padding: 0 4px;
        }

        .section {
            margin-bottom: 5px;
            page-break-inside: avoid;
        }

        .section-title {
            background: #e9e9e9;
            border: 1px solid #888;
            border-bottom: 0;
            padding: 3px 4px;
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data th,
        .data td {
            border: 1px solid #aaa;
            padding: 2px 3px;
            line-height: 1.15;
        }

        .data th {
            background: #f5f5f5;
            font-weight: bold;
            text-align: left;
        }

        .data th:last-child,
        .data td:last-child {
            text-align: right;
            width: 27%;
        }

        .data .pct {
            width: 20%;
        }

        .data .num {
            text-align: right;
        }

        .muted {
            color: #666;
        }

        .mini {
            width: 100%;
            border-collapse: collapse;
        }

        .mini td {
            padding: 2px 3px;
            border-bottom: 1px solid #ccc;
        }

        .mini td:last-child {
            text-align: right;
            font-weight: bold;
        }

        .wide {
            margin-top: 4px;
        }

        .wide-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 4px 0;
            margin: 0 -4px;
            table-layout: fixed;
        }

        .wide-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 4px;
        }

        .note {
            border-top: 1px solid #aaa;
            margin-top: 5px;
            padding-top: 4px;
            font-size: 5.5px;
            color: #555;
            line-height: 1.35;
        }

        .footer {
            margin-top: 3px;
            text-align: right;
            font-size: 5.5px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="scope">
            <strong>HALAMAN 1 — RINGKASAN AGREGAT</strong><br>
            Mengikuti KK yang sedang diekspor
        </div>
        <div class="title">Rekapitulasi Data Kartu Keluarga</div>
        <div class="subtitle">{{ $tenant->name }}</div>
        <div class="meta">
            {{ $tenant->village ?: '-' }}, {{ $tenant->district ?: '-' }}, {{ $tenant->city ?: '-' }}, {{ $tenant->province ?: '-' }}
            &nbsp; | &nbsp; Dicetak {{ $printedAt->format('d-m-Y H:i') }} WIB
        </div>
        <div class="clearfix"></div>
    </div>

    <table class="kpi">
        <tr>
            <td><span class="value">{{ number_format($aggregate['total_families']) }}</span><span class="label">Total KK</span></td>
            <td><span class="value">{{ number_format($aggregate['total_members']) }}</span><span class="label">Total Penduduk</span></td>
            <td><span class="value">{{ number_format($aggregate['average_members']) }}</span><span class="label">Rata-rata Anggota/KK</span></td>
            <td><span class="value">{{ number_format($aggregate['median_members']) }}</span><span class="label">Median Anggota/KK</span></td>
            <td><span class="value">{{ number_format($aggregate['minimum_members']) }}–{{ number_format($aggregate['maximum_members']) }}</span><span class="label">Min–Maks Anggota</span></td>
            <td><span class="value">{{ number_format($aggregate['active_families']) }}</span><span class="label">KK Aktif</span></td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">1. Jenis Kelamin</div>
                    <table class="data">
                        <tr>
                            <th>Jenis kelamin</th>
                            <th class="pct">Jumlah</th>
                            <th class="pct">%</th>
                        </tr>
                        @foreach ($aggregate['gender'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                            <td class="num">{{ number_format($aggregate['gender_percentages'][$label] ?? 0, 1) }}%</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">2. Kelompok Umur</div>
                    <table class="data">
                        <tr>
                            <th>Kelompok umur</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($aggregate['age_groups'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">3. Status Perkawinan</div>
                    <table class="data">
                        <tr>
                            <th>Status</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($aggregate['marital_status'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">4. Hubungan dengan Kepala Keluarga</div>
                    <table class="data">
                        <tr>
                            <th>Hubungan</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($aggregate['relationships'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">5. Pendidikan</div>
                    <table class="data">
                        <tr>
                            <th>Pendidikan</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($aggregate['education'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </td>

            <td>
                <div class="section">
                    <div class="section-title">6. Agama</div>
                    <table class="data">
                        <tr>
                            <th>Agama</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($aggregate['religion'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">7. Pekerjaan</div>
                    <table class="data">
                        <tr>
                            <th>Pekerjaan</th>
                            <th>Jumlah</th>
                        </tr>
                        @foreach ($aggregate['occupation'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">8. Karakteristik KK</div>
                    <table class="data">
                        <tr>
                            <td>KK hanya 1 orang</td>
                            <td class="num">{{ number_format($aggregate['single_member_families']) }}</td>
                        </tr>
                        <tr>
                            <td>KK dengan ≥5 anggota</td>
                            <td class="num">{{ number_format($aggregate['large_families']) }}</td>
                        </tr>
                        <tr>
                            <td>KK memiliki anak</td>
                            <td class="num">{{ number_format($aggregate['families_with_children']) }}</td>
                        </tr>
                        <tr>
                            <td>KK memiliki lansia</td>
                            <td class="num">{{ number_format($aggregate['families_with_elderly']) }}</td>
                        </tr>
                        <tr>
                            <td>WNI</td>
                            <td class="num">{{ number_format($aggregate['wni']) }}</td>
                        </tr>
                        <tr>
                            <td>WNA</td>
                            <td class="num">{{ number_format($aggregate['wna']) }}</td>
                        </tr>
                    </table>
                </div>
            </td>

            <td>
                <div class="section">
                    <div class="section-title">9. Distribusi Wilayah — KK per RT</div>
                    <table class="data">
                        <tr>
                            <th>RT</th>
                            <th>Jumlah KK</th>
                        </tr>
                        @foreach ($aggregate['rt'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">10. Penduduk per RT / RW</div>
                    <table class="data">
                        <tr>
                            <th>RT / RW</th>
                            <th>Jumlah penduduk</th>
                        </tr>
                        @foreach ($aggregate['rt_rw'] as $label => $total)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($total) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">11. Distribusi Kelurahan</div>
                    <table class="data">
                        <tr>
                            <th>Kelurahan</th>
                            <th>KK</th>
                            <th>Penduduk</th>
                        </tr>
                        @foreach ($aggregate['kelurahan'] as $label => $values)
                        <tr>
                            <td>{{ $label }}</td>
                            <td class="num">{{ number_format($values['kk']) }}</td>
                            <td class="num">{{ number_format($values['penduduk']) }}</td>
                        </tr>
                        @endforeach
                    </table>
                </div>

                <div class="section">
                    <div class="section-title">12. Ringkasan Ukuran KK</div>
                    <table class="mini">
                        <tr>
                            <td>Minimum anggota keluarga</td>
                            <td>{{ number_format($aggregate['minimum_members']) }}</td>
                        </tr>
                        <tr>
                            <td>Maksimum anggota keluarga</td>
                            <td>{{ number_format($aggregate['maximum_members']) }}</td>
                        </tr>
                        <tr>
                            <td>Median anggota keluarga</td>
                            <td>{{ number_format($aggregate['median_members']) }}</td>
                        </tr>
                        <tr>
                            <td>Rata-rata anggota keluarga</td>
                            <td>{{ number_format($aggregate['average_members']) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="note">
        Catatan: agregat pada halaman ini mengikuti KK yang sedang diekspor. Kelompok umur dihitung dari tanggal lahir yang tercatat. Pekerjaan dan pendidikan adalah nilai yang tercatat pada data penduduk, bukan indikator otomatis kondisi ekonomi. Agregat RT/RW dan kelurahan menggunakan alamat yang tercatat pada data KK.
    </div>
    <div class="footer">Halaman berikutnya: satu Kartu Keluarga per halaman, berurutan berdasarkan nomor KK.</div>
</body>

</html>