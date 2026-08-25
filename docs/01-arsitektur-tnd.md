# DANUM — Arsitektur Tata Naskah Dinas (TND)

**Status:** Draft arsitektur aktif  
**Scope:** Surat Keluar  
**Terakhir diperbarui:** 25 Agustus 2026

## 1. Tujuan

DANUM diarahkan menjadi platform persuratan yang menerapkan Tata Naskah Dinas (TND) secara **terpusat dalam konfigurasi**, tetapi digunakan secara **terdistribusi oleh OPD/unit kerja**.

Prinsip utama:

> Kompleksitas TND dikelola oleh administrator TND/Ortal; pengalaman pengguna OPD dibuat sederhana.

User OPD tidak perlu memahami seluruh klasifikasi TND. User cukup memilih surat yang ingin dibuat. Sistem menentukan apakah unit tersebut berwenang dan konfigurasi/template apa yang harus digunakan.

## 2. Fondasi DANUM yang dipertahankan

Arsitektur baru tidak mengganti fondasi yang sudah ada:

- `Tenant` menjadi representasi organisasi/tenant pada implementasi sekarang.
- `User` menjadi pengguna sistem.
- `UserRole` dan policy menjadi dasar authorization.
- `LetterType` menjadi fondasi master jenis surat.
- `LetterTypeVersion`/template menjadi fondasi versioning.
- `OutgoingLetter` menjadi entitas surat keluar.
- status history menjadi audit lifecycle surat.
- public verification menjadi pemeriksaan dokumen berdasarkan verification token.

Perubahan dilakukan secara evolutif, bukan dengan membangun ulang sistem dari nol.

## 3. Model konseptual

```text
Organization / Tenant
        |
        +---- Users / Roles
        |
        +---- Document Type / Letter Type
                    |
                    +---- Permission / Policy per Organization
                    |
                    +---- Template
                              |
                              +---- Template Version
                                        |
                                        v
                                 Outgoing Letter
                                        |
                         +--------------+--------------+
                         v                             v
                      Preview                       Issue
                                                       |
                                                       v
                                                 Verification
```

## 4. Configuration-driven

Aturan TND harus menjadi **data/configuration**, bukan hard-code berdasarkan nama tenant, jenis organisasi, atau role.

Jangan membuat logika seperti:

```php
if ($tenant->type === 'kelurahan') {
    // hide Surat Tugas
}
```

Gunakan hubungan konfigurasi:

```text
Organization
    -> DocumentTypePermission
    -> DocumentType
```

Dengan demikian administrator dapat mengubah kewenangan tanpa perubahan source code.

## 5. Pemisahan tanggung jawab

### Administrator TND / Ortal

Bertanggung jawab atas konfigurasi TND, termasuk:

- jenis surat;
- deskripsi dan kode jenis surat;
- template;
- versi template;
- masa berlaku konfigurasi;
- unit yang berwenang menggunakan jenis surat;
- aturan terkait penandatangan jika sudah tersedia;
- riwayat perubahan konfigurasi.

### User OPD

Bertanggung jawab atas penggunaan konfigurasi:

- melihat jenis surat yang tersedia untuk unitnya;
- membuat draft;
- mengisi data surat;
- preview;
- melanjutkan proses surat sesuai kewenangan;
- melihat surat unitnya.

User OPD tidak boleh mengubah konfigurasi TND, template resmi, atau kewenangan unit.

## 6. Role vs tanggung jawab domain

`Super Admin` saat ini merupakan role sistem dengan akses luas. Jangan langsung menganggap `Super Admin` sama dengan `Administrator TND/Ortal`.

Arah desain yang disarankan:

```text
Super Admin
  +-- System Administration
  +-- TND Administration

TND Admin / Ortal
  +-- TND configuration

Tenant Admin
  +-- tenant operational administration

Tenant User
  +-- operational letter usage
```

Pemisahan detail permission dapat dikembangkan tanpa mengubah model organisasi.

## 7. Alur user OPD

```text
Login
  -> Surat Keluar
  -> Buat Surat
  -> Sistem membaca tenant/unit user
  -> Sistem mengambil jenis surat yang diizinkan
  -> User memilih jenis surat
  -> Sistem memilih template version aktif
  -> Sistem menampilkan form sesuai konfigurasi
  -> User mengisi data
  -> Preview
  -> Simpan / proses
```

User tidak perlu memilih:

- template;
- versi template;
- font;
- margin;
- kop;
- struktur layout;
- aturan TND internal.

## 8. Alur administrator TND

```text
Kelola Jenis Surat
       |
       +--> Kelola Template
       |       |
       |       +--> Buat Version
       |       +--> Tentukan masa berlaku
       |
       +--> Kelola Kewenangan Unit
       |
       +--> Kelola Aturan Pendukung
       |
       +--> Audit perubahan
```

## 9. Batas scope saat ini

Fokus tahap ini adalah **Surat Keluar**.

Modul berikut tidak menjadi scope utama sampai kebutuhan tahap berikutnya ditetapkan:

- Surat Masuk;
- Disposisi;
- Arsip umum;
- modul persuratan lain di luar lifecycle Surat Keluar.

## 10. Prinsip desain

1. Backend boleh kompleks, UI OPD harus sederhana.
2. Authorization harus diterapkan di backend.
3. Menyembunyikan menu bukan authorization.
4. Tenant isolation harus tetap berlaku.
5. TND harus configuration-driven.
6. Template dan data surat harus dipisahkan.
7. Dokumen historis tidak boleh berubah karena konfigurasi baru.
8. Business rule harus diuji melalui workflow, bukan hanya method-level test.
9. Perubahan arsitektur harus diikuti regression test.
10. Jangan menambahkan klasifikasi TND yang belum diperlukan hanya demi kelengkapan teknis.
