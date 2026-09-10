# Buku Register Otomatis

## Status

Fitur ini mulai diimplementasikan pada September 2026.

## Tujuan

Buku Register DANUM adalah catatan administrasi surat keluar pada suatu tenant. Register tidak hanya mengambil surat yang dibuat DANUM, tetapi juga dapat mencatat surat yang dibuat secara manual di luar aplikasi.

## Dua sumber data

Setiap entri register memiliki sumber:

- `danum`: dibuat otomatis ketika `OutgoingLetter` berstatus `ISSUED`.
- `manual`: dimasukkan petugas untuk surat yang sudah dibuat di luar DANUM, misalnya saat internet atau listrik bermasalah.

Surat manual tidak dibuat sebagai `OutgoingLetter`. Ia hanya menjadi `RegisterEntry` sehingga buku register tetap lengkap.

## Nomor surat vs nomor register

Keduanya sengaja dipisahkan.

- Nomor surat adalah nomor resmi surat, misalnya `005/SETDA/2026`.
- Nomor register adalah nomor urut pencatatan pada buku register untuk tahun tersebut.

Nomor surat manual tidak dibuat ulang oleh DANUM. DANUM hanya memberikan nomor register berikutnya.

## Otomatisasi surat DANUM

Alurnya:

```text
DRAFT → SUBMITTED → VALIDATED → ISSUED → REGISTER ENTRY
```

Pembuatan register dibuat idempotent: satu `outgoing_letter` tidak boleh menghasilkan lebih dari satu entri register.

## Backfill surat lama

Setelah deployment pertama fitur ini, surat DANUM yang sudah berstatus `ISSUED` sebelum fitur aktif belum otomatis tersentuh oleh event baru. Gunakan command berikut satu kali untuk mengisi register yang belum ada:

```powershell
php artisan register:backfill
```

Untuk satu tenant saja:

```powershell
php artisan register:backfill --tenant=<TENANT_UUID>
```

Command hanya memproses surat `ISSUED` yang belum mempunyai `RegisterEntry`.

## Surat manual

Petugas tenant dapat membuka `Buku Register → Surat Manual`.

Data utama:

- nomor surat
- tanggal surat
- jenis surat
- kode klasifikasi
- perihal
- tujuan
- alamat tujuan
- penandatangan
- jabatan penandatangan

Sistem memeriksa agar nomor surat yang sama tidak tercatat dua kali pada tenant yang sama.

## Koreksi

Register yang berasal dari DANUM tidak diedit dari Buku Register. Register manual dapat dikoreksi dengan alasan wajib. Koreksi dicatat pada audit log dengan nilai lama, nilai baru, pengguna, dan waktu perubahan.

## Multi-tenant

Semua query register tenant dibatasi berdasarkan `tenant_id`. Pengguna tenant hanya melihat register tenant sendiri.

## API internal

```text
GET   /api/register-entries
POST  /api/register-entries
PATCH /api/register-entries/{id}
```

Endpoint tetap berada di balik autentikasi API internal DANUM.

## Struktur data

Tabel `register_entries` menyimpan snapshot administratif:

- `register_number`
- `register_year`
- `letter_number`
- `letter_date`
- `letter_type_name`
- `classification_code`
- `subject`
- `recipient_name`
- `recipient_address`
- `signer_name`
- `signer_title`
- `source`
- `status`
- `outgoing_letter_id`

`outgoing_letter_id` nullable karena surat manual tidak memiliki record `OutgoingLetter`.

## Prinsip

1. Register adalah catatan historis, bukan query langsung ke surat aktif.
2. Surat DANUM masuk register ketika benar-benar `ISSUED`.
3. Surat manual dicatat tanpa membuat surat palsu di workflow DANUM.
4. Nomor register terpisah dari nomor surat.
5. Koreksi manual wajib beralasan dan diaudit.
6. Data antar-tenant tidak boleh bercampur.

## Pengembangan berikutnya

1. cetak Buku Register PDF dengan format administrasi pemerintahan;
2. export Excel/CSV;
3. deteksi `potential gap` nomor surat;
4. halaman detail register;
5. statistik register per tahun/klasifikasi;
6. pengaturan kolom register per kebutuhan tenant;
7. pengujian otomatis untuk workflow `ISSUED`, manual entry, duplicate number, tenant isolation, dan correction audit.
