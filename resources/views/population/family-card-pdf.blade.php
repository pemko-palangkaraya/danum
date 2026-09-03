<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Keluarga {{ $family->no_kk }}</title>
    <style>
        @page { margin: 18px 22px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; margin: 0; }
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
        .religion { width: 7%; }
        .education { width: 9%; }
        .job { width: 11%; }
        .marital { width: 8%; }
        .relation { width: 9%; }
        .nationality { width: 6%; text-align: center; }
        .footer { margin-top: 10px; width: 100%; }
        .footer td { vertical-align: top; padding: 0 4px; }
        .note { width: 68%; font-size: 7px; color: #444; line-height: 1.4; }
        .signature { width: 32%; text-align: center; }
        .signature-space { height: 38px; }
        .muted { color: #555; }
    </style>
</head>
<body>
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
                    <td class="sex">{{ $citizen?->jenis_kelamin ?? '-' }}</td>
                    <td class="birth">{{ $citizen?->tempat_lahir ?? '-' }}, {{ $citizen?->tanggal_lahir?->format('d-m-Y') ?? '-' }}</td>
                    <td class="religion">{{ $citizen?->agama ?? '-' }}</td>
                    <td class="education">{{ $citizen?->pendidikan ?? '-' }}</td>
                    <td class="job">{{ $citizen?->pekerjaan ?? '-' }}</td>
                    <td class="marital">{{ $citizen?->status_perkawinan ?? '-' }}</td>
                    <td class="relation">{{ $member->hubungan_dalam_keluarga ?: '-' }}</td>
                    <td class="nationality">{{ $citizen?->kewarganegaraan ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center; padding:10px;">Belum ada anggota keluarga aktif.</td>
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
</body>
</html>
