# DANUM — Roadmap Implementasi TND

**Status:** Aktif  
**Baseline:** Arsitektur dan test suite saat 25 Agustus 2026

## 1. Prinsip roadmap

Implementasi dilakukan bertahap. Jangan membangun seluruh konsep TND sekaligus.

Setiap tahap harus:

1. sesuai dokumentasi arsitektur;
2. mempertahankan tenant isolation;
3. mempertahankan authorization backend;
4. menambah regression test;
5. tidak merusak dokumen historis.

## 2. Tahap 0 — Foundation

**Status: sebagian besar selesai**

Sudah tersedia:

- authentication;
- tenant;
- user management;
- role authorization;
- tenant isolation;
- tenant profile authorization;
- PostgreSQL testing;
- timezone `Asia/Pontianak`;
- live server clock;
- outgoing letter lifecycle dasar;
- public verification;
- regression suite.

Baseline test saat roadmap ini dibuat: **49 passing tests**.

## 3. Tahap 1 — TND Master

**Target berikutnya**

Bangun master terpusat:

- Document Type / Letter Type;
- kode dan nama;
- deskripsi user-friendly;
- status active/inactive;
- kategori bila benar-benar diperlukan;
- konfigurasi field/form;
- template terkait.

Acceptance criteria:

- admin dapat mengelola jenis surat;
- jenis surat inactive tidak tersedia untuk surat baru;
- perubahan tidak membutuhkan hard-code;
- seluruh perubahan dilindungi authorization.

## 4. Tahap 2 — Document Type Permission

Tambahkan konfigurasi:

```text
Organization / Tenant
        +
Document Type
        = Permission
```

Acceptance criteria:

- unit hanya melihat jenis surat yang diizinkan;
- backend menolak request ilegal;
- Super Admin/TND Admin sesuai policy dapat mengatur permission;
- perubahan permission berlaku untuk surat baru;
- surat lama tidak berubah.

## 5. Tahap 3 — Template Management

Bangun:

- template;
- template version;
- status;
- effective from;
- effective until;
- change note;
- audit metadata.

Acceptance criteria:

- user OPD tidak memilih template secara bebas;
- sistem memilih active version;
- versi lama tetap dapat direferensikan;
- versi yang telah digunakan tidak merusak histori.

## 6. Tahap 4 — Dynamic Letter Form

Setiap jenis surat dapat menentukan data yang perlu diisi user.

Contoh:

```text
Surat Tugas
  - dasar
  - nama yang ditugaskan
  - jabatan
  - tujuan
  - waktu
  - tempat
  - keperluan
  - pejabat penandatangan
```

UI menampilkan form sederhana. Detail TND tetap berada pada konfigurasi.

## 7. Tahap 5 — Outgoing Letter Integration

Integrasikan master TND dengan `OutgoingLetter`:

```text
User
 -> allowed Document Type
 -> active Template Version
 -> Dynamic Form
 -> Draft
 -> Preview
 -> Workflow
 -> Issue
 -> Verification
```

Pastikan surat menyimpan reference ke konfigurasi yang digunakan.

## 8. Tahap 6 — Historical Snapshot

Setelah reference version stabil, implementasikan snapshot untuk konfigurasi penting.

Target:

```text
OutgoingLetter
  -> template version
  -> TND/config snapshot
  -> audit history
```

Acceptance criteria:

- perubahan TND tidak mengubah dokumen lama;
- dokumen lama dapat direkonstruksi;
- audit dapat menjelaskan konfigurasi saat surat diterbitkan.

## 9. Tahap 7 — Signing Rules

Setelah master TND stabil, tambahkan aturan pejabat/penandatangan.

Jangan hard-code:

```php
if ($role === '...') ...
```

Gunakan configuration/policy yang dapat diaudit.

## 10. Tahap 8 — Hardening

Tambahkan:

- authorization matrix lengkap;
- edge-case tests;
- audit review;
- concurrency considerations;
- historical integrity tests;
- public verification security tests;
- performance checks untuk daftar jenis surat dan permission.

## 11. Modul yang belum menjadi prioritas

Sampai scope Surat Keluar stabil, jangan memperluas implementasi ke:

- Surat Masuk;
- Disposisi;
- Arsip umum;
- workflow persuratan lain.

## 12. Definition of Done untuk setiap tahap

Sebuah tahap dianggap selesai jika:

- desain terdokumentasi;
- schema/model sesuai;
- authorization tersedia;
- tenant isolation aman;
- happy path tersedia;
- negative path tersedia;
- regression test lulus;
- full `php artisan test` lulus;
- perubahan tidak merusak historical data.

## 13. Urutan kerja yang disepakati

```text
Dokumentasi
    ↓
Model/schema
    ↓
Authorization
    ↓
Service/business logic
    ↓
UI
    ↓
Feature tests
    ↓
Full regression
```

Jika ada perubahan kebutuhan, update dokumentasi dan roadmap sebelum mengubah implementasi yang berdampak luas.
