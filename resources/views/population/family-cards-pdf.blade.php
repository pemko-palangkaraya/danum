<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Keluarga - {{ $tenant->name }}</title>
    <style>
        @page { margin: 18px 22px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; margin: 0; }
        .aggregate-header { text-align: center; margin: 18px 0 14px; }
        .aggregate-header .title { font-size: 18px; font-weight: bold; }
        .aggregate-header .tenant { font-size: 12px; font-weight: bold; margin-top: 5px; }
        .aggregate-header .meta { font-size: 8px; margin-top: 4px; color: #555; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 6px; margin-bottom: 10px; }
        .summary td { width: 25%; border: 1px solid #222; padding: 10px; text-align: center; }
        .summary .number { display: block; font-size: 18px; font-weight: bold; }
        .summary .label { display: block; margin-top: 3px; font-size: 8px; font-weight: bold; }
        .breakdown { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .breakdown th, .breakdown td { border: 1px solid #222; padding: 6px; }
        .breakdown th { text-align: left; background: #f1f1f1; }
        .breakdown td:last-child { text-align: right; }
        .note { margin-top: 14px; font-size: 7px; color: #555; line-height: 1.5; }
        .family-page { page-break-before: always; }
        .header { border: 1px solid #222; padding: 9px 12px 8px; text-align: center; margin-bottom: 7px; }
        .title { font-size: 15px; font-weight: bold; letter-spacing: .4px; }
        .subtitle { font-size: 8px; margin-top: 3px; }
        .identity { width: 100%; border-collapse: collapse; margin-bottom: 7px; }
        .identity td { padding: 2px 3px; vertical-align: top; }
        .label { width: 11%; font-weight: bold; }
        .value { width: 39%; }
        table.members { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .members th, .members td { border: 1px solid #222; padding: 3px 3px; vertical-align: middle; }
        .members th { text-align: center; font-size: 6.7px; background: #f1f1f1; font-weight: bold; }
        .members td { font-size: 6.8px; line-height: 1.2; }
        .no { width: 3%; text-align: center; }
        .nik { width: 11%; font-family: monospace; }
        .name { width: 14%; }
        .sex { width: 6%; text-align: center; }
        .birth { width: 11%; }
        .blood { width: 5%; text-align: center; }
        .religion { width: 7%; }
        .education { width: 9%; }
        .job { width: 11%; }
        .marital { width: 8%; }
        .relation { width: 9%; }
        .nationality { width: 6%; text-align: center; }
        .footer { margin-top: 10px; width: 100%; }
        .footer td { vertical-align: top; padding: 0 4px; }
        .footer .note { width: 68%; margin-top: 0; }
        .signature { width: 32%; text-align: center; }
        .signature-space { height: 38px; }
        .muted { color: #555; }
    </style>
</head>
<body>
    <div class="aggregate-header">
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
            <td><span class="number">{{ number_format($families->where('status', 'active')->count()) }}</span><span class="label">KK Aktif</span></td>
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
            <tr><td>Jumlah anggota keluarga aktif</td><td>{{ number_format($aggregate['total_members']) }}</td></tr>
            <tr><td>Anggota laki-laki</td><td>{{ number_format($aggregate['male']) }}</td></tr>
            <tr><td>Anggota perempuan</td><td>{{ number_format($aggregate['female']) }}</td></tr>
            <tr><td>Warga Negara Indonesia</td><td>{{ number_format($aggregate['wni']) }}</td></tr>
            <tr><td>Warga Negara Asing</td><td>{{ number_format($aggregate['wna']) }}</td></tr>
        </tbody>
    </table>

    <div class="note">
        Halaman ini merupakan halaman agregat dari seluruh Kartu Keluarga pada tenant yang dicetak. Halaman berikutnya berisi Kartu Keluarga satu per satu sesuai urutan nomor KK.
    </div>

    @foreach($families as $family)
        <div class="family-page">
            <div class="header">
                <div class="title">KARTU KELUARGA</div>
                <div class="subtitle">Data kependudukan yang tersimpan pada DANUM</div>
            </div>

            <table class="identity">
                <tr>
                    <td class="label">No. KK</td>
                    <td class="value"><strong>{{ $family->no_kk }}</strong></td>
                    <td class="label">Kepala Keluarga</td>
                    <td class="value"><strong>{{ $family->headCitizen?->nama_lengkap ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Alamat</td>
                    <td class="value">{{ $family->alamat ?: '-' }}</td>
                    <td class="label">RT / RW</td>
                    <td class="value">{{ $family->rt ?: '-' }} / {{ $family->rw ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Kelurahan</td>
                    <td class="value">{{ $family->kelurahan ?: '-' }}</td>
                    <td class="label">Kecamatan</td>
                    <td class="value">{{ $family->kecamatan ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Kabupaten/Kota</td>
                    <td class="value">{{ $family->kabupaten_kota ?: '-' }}</td>
                    <td class="label">Provinsi</td>
                    <td class="value">{{ $family->provinsi ?: '-' }}</td>
                </tr>
            </table>

            <table class="members">
                <thead>
                    <tr>
                        <th class="no">No</th>
                        <th class="name">Nama Lengkap</th>
                        <th class="nik">NIK</th>
                        <th class="sex">Jenis<br>Kelamin</th>
                        <th class="birth">Tempat, Tanggal Lahir</th>
                        <th class="blood">Gol.<br>Darah</th>
                        <th class="religion">Agama</th>
                        <th class="education">Pendidikan</th>
                        <th class="job">Pekerjaan</th>
                        <th class="marital">Status<br>Perkawinan</th>
                        <th class="relation">Status Hubungan<br>Dalam Keluarga</th>
                        <th class="nationality">Kewarga-<br>negaraan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($family->activeMembers as $index => $member)
                        @php($citizen = $member->citizen)
                        <tr>
                            <td class="no">{{ $index + 1 }}</td>
                            <td class="name">{{ $citizen?->nama_lengkap ?? '-' }}</td>
                            <td class="nik">{{ $citizen?->nik ?? '-' }}</td>
                            <td class="sex">{{ $referenceLabels['gender'][$citizen?->jenis_kelamin] ?? ($citizen?->jenis_kelamin ?: '-') }}</td>
                            <td class="birth">{{ $citizen?->tempat_lahir ?? '-' }}, {{ $citizen?->tanggal_lahir?->format('d-m-Y') ?? '-' }}</td>
                            <td class="blood">{{ $referenceLabels['blood_type'][$citizen?->golongan_darah] ?? ($citizen?->golongan_darah ?: '-') }}</td>
                            <td class="religion">{{ $referenceLabels['religion'][$citizen?->agama] ?? ($citizen?->agama ?: '-') }}</td>
                            <td class="education">{{ $citizen?->pendidikan ?? '-' }}</td>
                            <td class="job">{{ $citizen?->pekerjaan ?? '-' }}</td>
                            <td class="marital">{{ $referenceLabels['marital_status'][$citizen?->status_perkawinan] ?? ($citizen?->status_perkawinan ?: '-') }}</td>
                            <td class="relation">{{ $referenceLabels['family_relationship'][$member->hubungan_dalam_keluarga] ?? ($member->hubungan_dalam_keluarga ?: '-') }}</td>
                            <td class="nationality">{{ $referenceLabels['citizenship'][$citizen?->kewarganegaraan] ?? ($citizen?->kewarganegaraan ?: '-') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" style="text-align:center; padding:10px;">Belum ada anggota keluarga aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <table class="footer">
                <tr>
                    <td class="note">
                        Dicetak {{ $printedAt->format('d-m-Y H:i') }} WIB.<br>
                        <span class="muted">Dokumen ini merupakan hasil cetak data kependudukan dari aplikasi DANUM. Pastikan data telah diperiksa sebelum digunakan untuk keperluan administrasi.</span>
                    </td>
                    <td class="signature">
                        {{ $family->tenant?->city ?: $family->kabupaten_kota ?: 'Palangka Raya' }}, {{ $printedAt->translatedFormat('d F Y') }}<br>
                        <strong>{{ $family->tenant?->head_title ?: 'Penanggung Jawab' }}</strong>
                        <div class="signature-space"></div>
                        <strong><u>{{ $family->tenant?->head_name ?: $family->headCitizen?->nama_lengkap ?: '-' }}</u></strong>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
