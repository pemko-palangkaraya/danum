<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Keluarga</title>
    <style>
        @page { margin: 14px 18px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 7px; color: #111; margin: 0; }

        .family-card { page-break-after: always; }
        .family-card:last-child { page-break-after: auto; }

        .title { text-align: center; font-size: 17px; font-weight: bold; margin: 0 0 1px; }
        .kk-number { text-align: center; font-size: 14px; font-weight: bold; letter-spacing: 2px; margin-bottom: 6px; }

        .identity { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        .identity td { padding: 1.5px 3px; vertical-align: top; }
        .identity .label { width: 12%; white-space: nowrap; }
        .identity .value-left { width: 38%; }
        .identity .label-right { width: 14%; white-space: nowrap; }
        .identity .value-right { width: 36%; }

        table.members, table.details { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .members th, .members td, .details th, .details td { border: 1px solid #222; padding: 2px; vertical-align: middle; }
        .members th, .details th { text-align: center; font-weight: bold; background: #eee; line-height: 1.1; }
        .members th, .details th { font-size: 6.2px; }
        .members td, .details td { font-size: 6.3px; line-height: 1.15; }

        .no { width: 3%; text-align: center; }
        .name { width: 15%; }
        .nik { width: 12%; text-align: center; }
        .sex { width: 7%; text-align: center; }
        .birth { width: 13%; }
        .religion { width: 7%; }
        .education { width: 11%; }
        .job { width: 12%; }
        .blood { width: 8%; text-align: center; }

        .marital { width: 11%; }
        .marriage-date { width: 10%; text-align: center; }
        .relation { width: 12%; }
        .citizenship { width: 8%; text-align: center; }
        .passport { width: 10%; text-align: center; }
        .kitap { width: 10%; text-align: center; }
        .parent { width: 14.5%; }

        .footer { width: 100%; margin-top: 7px; border-collapse: collapse; }
        .footer td { vertical-align: top; padding: 0 3px; }
        .footer-left { width: 50%; padding-top: 8px !important; }
        .signature { width: 50%; text-align: center; }
        .signature-space { height: 38px; }
        .signature-name { font-weight: bold; text-decoration: underline; }
        .document-note { margin-top: 5px; font-size: 6.2px; line-height: 1.3; color: #444; }
    </style>
</head>
<body>
    @foreach($families ?? collect([$family]) as $family)
        <div class="family-card">
            <div class="title">KARTU KELUARGA</div>
            <div class="kk-number">No. {{ $family->no_kk }}</div>

            <table class="identity">
                <tr>
                    <td class="label">Nama Kepala Keluarga</td>
                    <td class="value-left">: <strong>{{ $family->headCitizen?->nama_lengkap ?? '-' }}</strong></td>
                    <td class="label-right">Desa/Kelurahan</td>
                    <td class="value-right">: {{ $family->kelurahan ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Alamat</td>
                    <td class="value-left">: {{ $family->alamat ?: '-' }}</td>
                    <td class="label-right">Kecamatan</td>
                    <td class="value-right">: {{ $family->kecamatan ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">RT/RW</td>
                    <td class="value-left">: {{ $family->rt ?: '-' }}/{{ $family->rw ?: '-' }}</td>
                    <td class="label-right">Kabupaten/Kota</td>
                    <td class="value-right">: {{ $family->kabupaten_kota ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Kode Pos</td>
                    <td class="value-left">: {{ $family->kode_pos ?: '-' }}</td>
                    <td class="label-right">Provinsi</td>
                    <td class="value-right">: {{ $family->provinsi ?: '-' }}</td>
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
                        <th class="job">Jenis Pekerjaan</th>
                        <th class="blood">Golongan<br>Darah</th>
                    </tr>
                    <tr>
                        <th>(1)</th><th>(2)</th><th>(3)</th><th>(4)</th><th>(5)</th>
                        <th>(6)</th><th>(7)</th><th>(8)</th><th>(9)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($family->activeMembers->take(10) as $index => $member)
                        @php($citizen = $member->citizen)
                        <tr>
                            <td class="no">{{ $index + 1 }}</td>
                            <td class="name">{{ $citizen?->nama_lengkap ?? '-' }}</td>
                            <td class="nik">{{ $citizen?->nik ?? '-' }}</td>
                            <td class="sex">{{ $referenceLabels['gender'][$citizen?->jenis_kelamin] ?? ($citizen?->jenis_kelamin ?: '-') }}</td>
                            <td class="birth">{{ $citizen?->tempat_lahir ?? '-' }}, {{ $citizen?->tanggal_lahir?->format('d-m-Y') ?? '-' }}</td>
                            <td class="religion">{{ $referenceLabels['religion'][$citizen?->agama] ?? ($citizen?->agama ?: '-') }}</td>
                            <td class="education">{{ $citizen?->pendidikan ?? '-' }}</td>
                            <td class="job">{{ $citizen?->pekerjaan ?? '-' }}</td>
                            <td class="blood">{{ $referenceLabels['blood_type'][$citizen?->golongan_darah] ?? ($citizen?->golongan_darah ?: '-') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" style="text-align:center;">Belum ada anggota keluarga aktif.</td></tr>
                    @endforelse
                    @for($row = $family->activeMembers->count() + 1; $row <= 10; $row++)
                        <tr>
                            <td class="no">{{ $row }}</td><td>-</td><td class="nik">-</td><td class="sex">-</td>
                            <td>-</td><td>-</td><td>-</td><td>-</td><td class="blood">-</td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <table class="details">
                <thead>
                    <tr>
                        <th class="no"></th>
                        <th colspan="2">Status Perkawinan</th>
                        <th class="relation">Status Hubungan<br>Dalam Keluarga</th>
                        <th class="citizenship">Kewarga-<br>negaraan</th>
                        <th colspan="2">Dokumen Imigrasi</th>
                        <th colspan="2">Nama Orang Tua</th>
                    </tr>
                    <tr>
                        <th class="no">No</th>
                        <th class="marital">Status</th>
                        <th class="marriage-date">Tanggal Perkawinan</th>
                        <th class="relation">Hubungan</th>
                        <th class="citizenship">WNI/WNA</th>
                        <th class="passport">No. Passport</th>
                        <th class="kitap">No. KITAP</th>
                        <th class="parent">Ayah</th>
                        <th class="parent">Ibu</th>
                    </tr>
                    <tr>
                        <th>(10)</th><th>(10)</th><th>(11)</th><th>(12)</th><th>(13)</th>
                        <th>(14)</th><th>(15)</th><th>(16)</th><th>(17)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($family->activeMembers->take(10) as $index => $member)
                        @php($citizen = $member->citizen)
                        <tr>
                            <td class="no">{{ $index + 1 }}</td>
                            <td class="marital">{{ $referenceLabels['marital_status'][$citizen?->status_perkawinan] ?? ($citizen?->status_perkawinan ?: '-') }}</td>
                            <td class="marriage-date">-</td>
                            <td class="relation">{{ $referenceLabels['family_relationship'][$member->hubungan_dalam_keluarga] ?? ($member->hubungan_dalam_keluarga ?: '-') }}</td>
                            <td class="citizenship">{{ $referenceLabels['citizenship'][$citizen?->kewarganegaraan] ?? ($citizen?->kewarganegaraan ?: '-') }}</td>
                            <td class="passport">{{ $citizen?->no_passport ?: '-' }}</td>
                            <td class="kitap">{{ $citizen?->no_kitap ?: '-' }}</td>
                            <td class="parent">{{ $citizen?->nama_ayah ?: '-' }}</td>
                            <td class="parent">{{ $citizen?->nama_ibu ?: '-' }}</td>
                        </tr>
                    @endforelse
                    @for($row = $family->activeMembers->count() + 1; $row <= 10; $row++)
                        <tr>
                            <td class="no">{{ $row }}</td><td>-</td><td class="marriage-date">-</td><td>-</td>
                            <td class="citizenship">-</td><td class="passport">-</td><td class="kitap">-</td><td>-</td><td>-</td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <table class="footer">
                <tr>
                    <td class="footer-left">
                        Dikeluarkan Tanggal: <strong>{{ $printedAt->format('d-m-Y') }}</strong>
                        <div class="document-note">
                            Dokumen ini merupakan hasil cetak data kependudukan dari aplikasi DANUM.
                            Pastikan data telah diperiksa sebelum digunakan untuk keperluan administrasi.
                        </div>
                    </td>
                    <td class="signature">
                        {{ $family->tenant?->city ?: $family->kabupaten_kota ?: 'Palangka Raya' }}, {{ $printedAt->translatedFormat('d F Y') }}<br>
                        <strong>{{ $family->tenant?->head_title ?: 'Penanggung Jawab' }}</strong>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $family->tenant?->head_name ?: $family->headCitizen?->nama_lengkap ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
