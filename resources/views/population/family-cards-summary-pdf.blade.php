<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Kartu Keluarga - {{ $tenant->name }}</title>
    <style>
        @page { margin: 18px 22px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 7.5px; color: #111; margin: 0; }
        .header { text-align: center; margin: 10px 0 10px; }
        .title { font-size: 15px; font-weight: bold; }
        .tenant { font-size: 10px; font-weight: bold; margin-top: 4px; }
        .meta { font-size: 7px; margin-top: 3px; color: #555; line-height: 1.35; }
        .cards { width: 100%; border-collapse: separate; border-spacing: 4px; margin-bottom: 6px; }
        .cards td { width: 20%; border: 1px solid #222; padding: 6px 4px; text-align: center; }
        .number { display: block; font-size: 13px; font-weight: bold; }
        .label { display: block; margin-top: 2px; font-size: 6.5px; font-weight: bold; }
        .section-title { font-size: 9px; font-weight: bold; margin: 8px 0 3px; }
        .columns { width: 100%; border-collapse: separate; border-spacing: 5px 0; }
        .columns > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        .block { margin-bottom: 7px; }
        table.data { width: 100%; border-collapse: collapse; }
        .data th, .data td { border: 1px solid #aaa; padding: 3px 4px; }
        .data th { background: #f1f1f1; text-align: left; font-weight: bold; }
        .data td:last-child, .data th:last-child { text-align: right; }
        .muted { color: #666; }
        .note { margin-top: 7px; font-size: 6.5px; color: #555; line-height: 1.4; }
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

    <table class="cards">
        <tr>
            <td><span class="number">{{ number_format($aggregate['total_families']) }}</span><span class="label">TOTAL KK</span></td>
            <td><span class="number">{{ number_format($aggregate['total_members']) }}</span><span class="label">TOTAL PENDUDUK</span></td>
            <td><span class="number">{{ number_format($aggregate['average_members'], 2) }}</span><span class="label">RATA-RATA ANGGOTA/KK</span></td>
            <td><span class="number">{{ number_format($aggregate['median_members'], 2) }}</span><span class="label">MEDIAN ANGGOTA/KK</span></td>
            <td><span class="number">{{ number_format($aggregate['active_families']) }}</span><span class="label">KK AKTIF</span></td>
        </tr>
    </table>

    <table class="columns">
        <tr>
            <td>
                <div class="block">
                    <div class="section-title">1. Jenis Kelamin</div>
                    <table class="data">
                        <tr><th>Jenis kelamin</th><th>Jumlah</th><th>%</th></tr>
                        @foreach ($aggregate['gender'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td><td>{{ number_format($aggregate['gender_percentages'][$label] ?? 0, 2) }}%</td></tr>
                        @endforeach
                    </table>
                </div>

                <div class="block">
                    <div class="section-title">2. Kelompok Umur</div>
                    <table class="data">
                        <tr><th>Kelompok umur</th><th>Jumlah</th></tr>
                        @foreach ($aggregate['age_groups'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>

                <div class="block">
                    <div class="section-title">3. Status Perkawinan</div>
                    <table class="data">
                        <tr><th>Status</th><th>Jumlah</th></tr>
                        @foreach ($aggregate['marital_status'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>

                <div class="block">
                    <div class="section-title">4. Hubungan dengan Kepala Keluarga</div>
                    <table class="data">
                        <tr><th>Hubungan</th><th>Jumlah</th></tr>
                        @foreach ($aggregate['relationships'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td>
                <div class="block">
                    <div class="section-title">5. Pendidikan</div>
                    <table class="data">
                        <tr><th>Pendidikan</th><th>Jumlah</th></tr>
                        @foreach ($aggregate['education'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>

                <div class="block">
                    <div class="section-title">6. Agama</div>
                    <table class="data">
                        <tr><th>Agama</th><th>Jumlah</th></tr>
                        @foreach ($aggregate['religion'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>

                <div class="block">
                    <div class="section-title">7. Pekerjaan</div>
                    <table class="data">
                        <tr><th>Pekerjaan</th><th>Jumlah</th></tr>
                        @foreach ($aggregate['occupation'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>

                <div class="block">
                    <div class="section-title">8. Karakteristik KK</div>
                    <table class="data">
                        <tr><td>KK terkecil</td><td>{{ number_format($aggregate['minimum_members']) }} orang</td></tr>
                        <tr><td>KK terbesar</td><td>{{ number_format($aggregate['maximum_members']) }} orang</td></tr>
                        <tr><td>KK hanya 1 orang</td><td>{{ number_format($aggregate['single_member_families']) }}</td></tr>
                        <tr><td>KK dengan ≥5 anggota</td><td>{{ number_format($aggregate['large_families']) }}</td></tr>
                        <tr><td>KK memiliki anak</td><td>{{ number_format($aggregate['families_with_children']) }}</td></tr>
                        <tr><td>KK memiliki lansia</td><td>{{ number_format($aggregate['families_with_elderly']) }}</td></tr>
                        <tr><td>WNI</td><td>{{ number_format($aggregate['wni']) }}</td></tr>
                        <tr><td>WNA</td><td>{{ number_format($aggregate['wna']) }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">9. Agregat Wilayah / Alamat</div>
    <table class="columns">
        <tr>
            <td>
                <div class="block">
                    <table class="data">
                        <tr><th>RT</th><th>Jumlah KK</th></tr>
                        @foreach ($aggregate['rt'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </td>
            <td>
                <div class="block">
                    <table class="data">
                        <tr><th>RT / RW</th><th>Jumlah penduduk</th></tr>
                        @foreach ($aggregate['rt_rw'] as $label => $total)
                            <tr><td>{{ $label }}</td><td>{{ number_format($total) }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">10. Distribusi Kelurahan</div>
    <table class="data">
        <tr><th>Kelurahan</th><th>Jumlah KK</th><th>Jumlah penduduk</th></tr>
        @foreach ($aggregate['kelurahan'] as $label => $values)
            <tr><td>{{ $label }}</td><td>{{ number_format($values['kk']) }}</td><td>{{ number_format($values['penduduk']) }}</td></tr>
        @endforeach
    </table>

    <div class="note">
        Agregat pada halaman ini mengikuti KK yang sedang diekspor. Untuk pengujian saat ini, ekspor dibatasi maksimal 5 KK. Kelompok umur dihitung dari tanggal lahir yang tercatat. Pekerjaan dan pendidikan merupakan nilai yang tercatat pada data penduduk, bukan indikator otomatis kondisi ekonomi. Agregat RT/RW menggunakan alamat yang tercatat pada data KK.
    </div>
</body>
</html>
