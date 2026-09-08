# Rangkuman Pengembangan — Verifikasi Dokumen & TTE

Tanggal: 9 September 2026  
Branch: `master`

## 1. Fokus pekerjaan

Sesi ini berfokus pada penguatan **public verification** untuk Surat Keluar, terutama:

- level akses dokumen berdasarkan klasifikasi;
- audit trail khusus aktivitas verifikasi dan akses dokumen;
- hash SHA-256 dokumen final;
- verifikasi tanda tangan PDF secara kriptografis;
- pengujian akses PUBLIC / PROTECTED / RESTRICTED.

Seluruh perubahan dikerjakan langsung di `master`, tanpa membuat branch baru.

## 2. Level akses verifikasi

Ditambahkan enum `VerificationAccessLevel` dengan tiga level:

- `PUBLIC` — metadata, hash, dan akses dokumen dapat diberikan melalui verifikasi publik.
- `PROTECTED` — metadata/status verifikasi dapat dilihat, tetapi akses dokumen membutuhkan login dan authorization tenant.
- `RESTRICTED` — verifikasi metadata tetap tersedia, tetapi dokumen tidak dapat diakses melalui endpoint verifikasi publik.

Konfigurasi level akses ditambahkan ke `LetterClassification` melalui field `verification_access_level`.

## 3. Verification Log

Ditambahkan tabel/model `VerificationLog` dan service `VerificationLogService` untuk mencatat aktivitas verifikasi dan akses dokumen.

Informasi yang dicatat meliputi:

- document ID;
- waktu aktivitas;
- user ID dan tipe user;
- action;
- result;
- classification;
- access method;
- IP address;
- user-agent.

Action yang digunakan pada implementasi saat ini mencakup `VERIFY` dan `DOWNLOAD`.

## 4. Hash dokumen final

`OutgoingLetter` sekarang memiliki:

- `document_hash`;
- `document_hash_algorithm`.

Hash menggunakan **SHA-256** terhadap file PDF final yang tersedia. Callback model mengisi hash ketika dokumen tersimpan dan hash belum tersedia.

Hash juga dimasukkan ke audit values pada proses Surat Keluar sehingga perubahan penting terkait dokumen final dapat ditelusuri.

## 5. Verifikasi PDF/TTE

Dibuat `PdfSignatureVerificationService` untuk melakukan validasi terhadap PDF bertanda tangan.

Proses verifikasi saat ini mencakup:

1. memastikan signed PDF tersedia;
2. memastikan file benar-benar tersedia pada storage;
3. membandingkan hash SHA-256 file dengan hash yang tercatat;
4. mengambil `/ByteRange` dan `/Contents` signature dari PDF;
5. membentuk detached content dari byte range;
6. mengambil CMS/PKCS#7 signature;
7. memverifikasi `SignedData` menggunakan `tc-lib-pdf-sign`;
8. mengambil sertifikat penanda tangan;
9. membandingkan fingerprint SHA-256 sertifikat PDF dengan sertifikat penanda tangan yang tercatat jika tersedia;
10. mengembalikan status seperti `unsigned`, `invalid`, atau `valid` beserta alasan/metadata validasi.

Tujuannya agar status TTE tidak hanya didasarkan pada keberadaan `signed_pdf_path`, tetapi juga pada pemeriksaan kriptografis terhadap PDF.

## 6. Endpoint verifikasi

`VerificationController` sekarang menyediakan:

- halaman `/verify/{token}`;
- JSON `/verify/{token}/json`;
- akses dokumen `/verify/{token}/document`.

Perilaku endpoint dokumen:

### PUBLIC

Dokumen dapat diakses tanpa login sesuai konfigurasi akses publik.

### PROTECTED

- guest diarahkan ke login;
- user yang sudah login tetap harus lolos authorization;
- akses lintas tenant ditolak.

### RESTRICTED

Akses dokumen melalui endpoint verifikasi ditolak dengan `403`, dan penolakan dicatat ke verification log.

Dokumen ditampilkan inline sebagai PDF pada browser, bukan dipaksa sebagai attachment download.

## 7. UI verifikasi

Halaman verification menampilkan level akses dan status TTE.

Informasi utama yang ditampilkan meliputi:

- nomor surat;
- tanggal;
- jenis surat;
- tenant/kota;
- penanda tangan;
- jabatan penanda tangan;
- status TTE;
- hash dokumen;
- masa berlaku;
- status withdrawal.

Akses dokumen mengikuti level verifikasi:

- PUBLIC → tombol **Lihat Dokumen**;
- PROTECTED → informasi bahwa login diperlukan;
- RESTRICTED → tidak ada tombol akses dokumen.

## 8. Pengaturan klasifikasi

UI `Letter Classifications` sudah mendukung pemilihan:

- PUBLIC;
- PROTECTED;
- RESTRICTED.

Nilai tersebut menggunakan enum dan ikut dicatat pada audit perubahan konfigurasi klasifikasi.

## 9. Regression test

Ditambahkan `tests/Feature/VerificationAccessTest.php` dengan skenario:

1. PUBLIC dapat diverifikasi dan dokumen dapat diakses tanpa login.
2. PROTECTED membutuhkan login.
3. PROTECTED dapat diakses oleh user tenant yang berwenang.
4. PROTECTED dari tenant lain ditolak.
5. RESTRICTED dapat diverifikasi tetapi dokumen tidak dapat diakses.
6. PDF yang hash-nya tidak sesuai dilaporkan invalid.
7. Token verification yang tidak valid menghasilkan 404 dan tercatat pada verification log.

Hasil pengujian terakhir yang dijalankan:

```text
PASS  Tests\\Feature\\VerificationAccessTest
✓ public document can be verified and downloaded without login
✓ protected document requires login before download
✓ protected document can be downloaded by authorized tenant user
✓ protected document for wrong tenant is forbidden and logged
✓ restricted document can be verified but never downloaded
✓ tampered signed pdf is reported as invalid
✓ invalid verification token returns not found and is logged

Tests:    7 passed (38 assertions)
Duration: 3.65s
```

**Status: GREEN untuk test feature verification tersebut.**

## 10. Perbaikan test workflow penerbitan

Karena hash sekarang dihitung terhadap file PDF final, mock pada test workflow penerbitan perlu menyediakan file PDF pada storage.

Perbaikan dilakukan pada test workflow agar mock output PDF benar-benar dibuat sebelum proses hash dijalankan.

Audit values pada workflow juga sudah mencakup:

- `document_hash`;
- `document_hash_algorithm`.

## 11. Status TTE saat ini

Fondasi verifikasi TTE/PDF sudah tersedia dan verification access sudah memiliki regression test yang berjalan.

Namun, **real PAdES B-T + TSA end-to-end smoke test belum dianggap selesai/tervalidasi** dalam sesi ini.

Test live signing/TSA sebelumnya memang dipisahkan dari default test suite karena bergantung pada layanan TSA nyata.

## 12. Langkah berikutnya

Jika pengembangan TTE dilanjutkan, prioritas berikutnya:

1. audit ulang `PdfSigningService` dan `OutgoingLetterIssuanceService`;
2. pastikan hash selalu mengacu pada PDF final setelah proses signing;
3. buat deterministic signing test jika memungkinkan menggunakan certificate/key test yang benar-benar dapat digunakan;
4. pertahankan live TSA/PAdES B-T smoke test sebagai test terpisah dari default suite jika membutuhkan jaringan/TSA eksternal;
5. validasi kembali fingerprint sertifikat penanda tangan terhadap `SignerCertificate`;
6. pertimbangkan penguatan policy agar dokumen PUBLIC hanya dapat ditampilkan jika memang sudah diterbitkan/ditandatangani sesuai business rule.

## 13. Prinsip yang dipertahankan

- Semua perubahan langsung di `master`.
- Authorization tetap ditegakkan di backend.
- Tenant isolation tidak boleh dilewati.
- Public verification tidak boleh menjadi bypass authorization.
- Aktivitas verifikasi dan akses dokumen harus dapat diaudit.
- Dokumen historis dan hash final harus dapat ditelusuri.
- Regression test wajib mengikuti perubahan business rule.
- Hindari overengineering; implementasi mengikuti kebutuhan nyata DANUM.

## 14. Commit penting sesi ini

- `ef347a21728f12a4e2a8f3fce446258f0dff1f97` — update `LetterClassification`.
- `b60ea89eb6fba34db241fb3de8f338bd0b18f6d3` — tambah `VerificationLog` model.
- `0b3ac6f355a452c5afde2fb0dc41a8cf9add4260` — hash dan verification relation pada `OutgoingLetter`.
- `7bae166dc55687fe6f546da1726d04b378adb626` — `VerificationLogService`.
- `6cf7a0f5ea6970608783cf27613c90ebf6666e0c` — `PdfSignatureVerificationService`.
- `730c2685708aacea29b30da57e4e662dd1d73922` — integrasi PDF signature verification ke controller.
- `f4fe32319ed978050c63ad1739545f93c087b6f3` — endpoint verification JSON/document.
- `4dbdd4a320be77c304455e353ed9ea3bd69f394e` — update UI verification.
- `a1c29c1f142300b13c9c46a339c190f1188b7ef2` — UI/config verification access level.
- `244541de9b53c97c4067c321acab353f237e19d8` — perbaikan mock PDF pada workflow test.
- `3be189fc4bb141dec3319e545dedf9be08dab52a` — audit values hash final.
- `5cbe81b460a0025e259f938119825acf65889b75` — audit values hash pada `OutgoingLetterService`.

## Kesimpulan

Fondasi **document verification + access control + verification audit trail + SHA-256 integrity check** sudah berjalan dan memiliki regression test yang GREEN.

Bagian yang masih menjadi pekerjaan lanjutan adalah **validasi nyata PAdES B-T dengan TSA secara end-to-end**. Sampai smoke test tersebut dijalankan dan berhasil, jangan menganggap seluruh rantai TTE eksternal sudah tervalidasi penuh.
