# Kop Surat Terstruktur dan Standar Migrasi DANUM

## 1. Kop surat terstruktur

Kop surat DANUM menggunakan konfigurasi terstruktur pada tabel `tenants` dan dirender langsung ke DOCX.

Struktur visual yang menjadi acuan:

- logo berada di kolom kiri;
- teks kop berada di kolom kanan;
- proporsi layout DOCX menggunakan tabel 20% : 80%;
- garis bawah kop membentang penuh;
- rasio asli logo wajib dipertahankan;
- ukuran logo tidak boleh melebihi ruang kolom logo;
- preview pada halaman Profil Organisasi harus mengikuti layout DOCX, bukan sekadar kartu contoh.

Preview kop menggunakan halaman A4 agar pengguna awam dapat memahami hasil dokumen sebenarnya. Area preview dibuat scrollable di dalam modal sehingga halaman A4 tidak terpotong oleh viewport.

### Field kop

Field utama yang digunakan renderer terstruktur:

- `letterhead_line1`
- `letterhead_line2`
- `letterhead_line3`
- `address`
- `logo`
- `letterhead_line1_size`
- `letterhead_line2_size`
- `letterhead_line3_size`
- `letterhead_meta_size`

`address` adalah sumber manual tunggal untuk alamat dan informasi kontak kop. Pengguna dapat menulis kode pos, telepon, email, dan website di dalam field tersebut.

`postal_code` dan `website` tetap berada di schema untuk kompatibilitas data lama, tetapi bukan sumber otomatis renderer kop terstruktur.

### Aturan sizing logo DOCX

Jangan menggunakan batas ukuran logo global yang lebih besar daripada cell DOCX. Ukuran maksimum harus mengikuti ruang kolom logo dan dihitung berdasarkan dimensi asli gambar. Skala X dan Y harus sama agar logo tidak terdistorsi.

Perubahan ini mencegah logo melewati cell dan terpotong pada hasil LibreOffice/Word.

## 2. Standar migrasi saat development

DANUM saat ini masih dalam tahap development. Karena database dapat dibangun ulang dengan `php artisan migrate:fresh`, schema harus dipelihara sebagai **baseline yang bersih**, bukan kumpulan migration patch yang terus menumpuk.

### Prinsip

1. Migration `create_*_table` adalah sumber kebenaran schema untuk tabel yang masih aktif dikembangkan.
2. Jika sebuah perubahan schema belum perlu dipertahankan untuk upgrade production, gabungkan perubahan tersebut ke migration baseline dan hapus migration patch yang hanya diperlukan untuk development.
3. Jangan membuat migration baru hanya untuk menambal migration yang baru saja dibuat jika baseline masih dapat diperbaiki langsung.
4. Setiap migration harus memiliki timestamp yang unik dan urutan dependency yang jelas.
5. Migration tidak boleh bergantung pada tabel/kolom yang baru dibuat pada migration setelahnya.
6. `migrate:fresh` wajib menjadi smoke test setelah perubahan schema besar.
7. Seeder/data demo tidak boleh menjadi syarat agar struktur database dapat dibuat.

### Refaktor baseline tenant

Schema `tenants` sekarang langsung mendefinisikan seluruh konfigurasi kop surat yang aktif:

- `letterhead_path`
- `letterhead_line1`
- `letterhead_line2`
- `letterhead_line3`
- `letterhead_line1_size`
- `letterhead_line2_size`
- `letterhead_line3_size`
- `letterhead_meta_size`
- `postal_code`
- `website`
- `head_nip`

Dengan demikian migration tambahan berikut tidak lagi diperlukan untuk database development baru:

- `2026_08_23_120000_add_letterhead_path_to_tenants_table.php`
- `2026_09_10_220000_add_letterhead_settings_to_tenants_table.php`
- `2026_09_10_221000_add_letterhead_font_sizes_to_tenants_table.php`

Ketiganya telah dihapus dan schema digabungkan ke `2026_08_17_184528_create_tenants_table.php`.

### Catatan penting

Refaktor baseline seperti ini ditujukan untuk development sebelum schema dianggap production-stable. Setelah DANUM masuk fase production dan migration sudah dipakai pada database nyata, migration yang telah dieksekusi tidak boleh dihapus atau ditulis ulang sembarangan. Perubahan production harus menggunakan migration baru yang backward-compatible.

## 3. Checklist sebelum melanjutkan fitur

Jalankan pada database development:

```bash
php artisan migrate:fresh
php artisan db:seed
php artisan test
```

Jika `migrate:fresh` gagal, jangan menambahkan migration patch secara spontan. Perbaiki dependency atau baseline migration yang menjadi sumber masalah terlebih dahulu.

Target kita: **fresh database dapat dibangun dari nol secara deterministik, tanpa migration tambal-sulam.**
