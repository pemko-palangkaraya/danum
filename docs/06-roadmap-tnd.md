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

**Status: selesai**

Sudah tersedia authentication, tenant, user management, role authorization, tenant isolation, tenant profile authorization, PostgreSQL testing, timezone `Asia/Pontianak`, live server clock, outgoing letter lifecycle, public verification, dan regression suite.

## 3. Tahap 1 — TND Master

**Status: selesai**

Sudah tersedia master Letter Type global dengan kode, nama, deskripsi, status, variabel, template DOCX, dan konfigurasi masa berlaku. Seluruh pengelolaan dilindungi authorization Super Admin.

## 4. Tahap 2 — Document Type Permission

**Status: selesai**

Sudah tersedia permission per Tenant/OPD untuk Letter Type global. Enforcement berlaku pada daftar jenis surat dan backend pembuatan surat baru. Surat lama tidak berubah ketika permission berubah.

## 5. Tahap 3 — Template Management

**Status: selesai**

Sudah tersedia:

- `LetterTypeVersion`;
- template DOCX per versi;
- `effective_from` dan `effective_until`;
- `is_active`;
- `change_note`;
- `created_by`;
- pemilihan versi aktif berdasarkan periode;
- penguncian reference versi pada `OutgoingLetter`;
- UI riwayat dan pembuatan versi oleh Super Admin;
- audit log pembuatan versi;
- preservasi file template historis.

Acceptance criteria terpenuhi:

- user OPD tidak memilih template version secara bebas;
- sistem memilih active version;
- versi lama tetap dapat direferensikan;
- versi yang sudah digunakan tidak dimutasi;
- perubahan template menghasilkan version baru;
- periode versi tidak boleh overlap;
- template file lama tidak dihapus ketika versi baru dibuat.

## 6. Tahap 4 — Dynamic Letter Form

**Target berikutnya**

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

**Status: fondasi selesai, perlu penyempurnaan dynamic form/snapshot**

Integrasi yang sudah berjalan:

```text
User
 -> allowed Document Type
 -> active Template Version
 -> Draft
 -> Preview
 -> Workflow
 -> Issue
 -> Verification
```

OutgoingLetter menyimpan reference ke `letter_type_version_id` sehingga dokumen tidak mengikuti perubahan template setelah dibuat.

## 8. Tahap 6 — Historical Snapshot

**Target berikutnya setelah Dynamic Letter Form**

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

Jangan hard-code role. Gunakan configuration/policy yang dapat diaudit.

## 10. Tahap 8 — Hardening

Tambahkan authorization matrix lengkap, edge-case tests, audit review, concurrency considerations, historical integrity tests, public verification security tests, dan performance checks.

## 11. Modul yang belum menjadi prioritas

Sampai scope Surat Keluar stabil, jangan memperluas implementasi ke:

- Surat Masuk;
- Disposisi;
- Arsip umum;
- workflow persuratan lain.

## 12. Definition of Done untuk setiap tahap

Sebuah tahap dianggap selesai jika desain terdokumentasi, schema/model sesuai, authorization tersedia, tenant isolation aman, happy path tersedia, negative path tersedia, regression test lulus, full `php artisan test` lulus, dan perubahan tidak merusak historical data.

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
