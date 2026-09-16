# DANUM — Checkpoint Pengembangan 16 September 2026

**Tanggal:** 16 September 2026  
**Status:** Checkpoint pengembangan aktif  
**Fokus hari ini:** Surat Keluar, lampiran PDF, letterhead, user/employee profile, dan konsistensi RBAC.

Dokumen ini mencatat hasil pekerjaan hari ini, error yang ditemukan beserta penyelesaiannya, fitur yang ditambahkan/diperbaiki, serta rencana lanjutan.

---

## 1. Ringkasan hasil hari ini

Hari ini dilakukan beberapa perbaikan yang saling berkaitan:

1. Memperbaiki schema `letter_types` dan error `font_family`.
2. Merapikan migration history agar schema final tidak bergantung pada migration patch yang bersifat tambal sulam.
3. Menambahkan sistem **Lampiran Surat Keluar** berbasis PDF.
4. Menambahkan marker `{{lampiran}}` pada sistem variabel/template surat.
5. Mengintegrasikan lampiran ke preview dan penerbitan Surat Keluar.
6. Menambahkan header lampiran pada PDF gabungan.
7. Memperbaiki letterhead agar Enter/manual line break pada alamat tenant dipertahankan.
8. Memperbaiki form **Tenant Users** agar data kepegawaian lengkap dapat dikelola dari tenant.
9. Memastikan data kepegawaian disimpan melalui `employee_profiles`, bukan menduplikasi kolom ke tabel `users`.
10. Mempertahankan pemisahan antara data kepegawaian dan struktur jabatan/RBAC.

---

## 2. Error yang ditemukan dan penyelesaiannya

### 2.1 Error `font_family` pada `letter_types`

**Gejala:** saat membuat Letter Type muncul error database:

```text
column "font_family" of relation "letter_types" does not exist
```

**Penyebab:** kode repository sudah mengharapkan field `font_family`, tetapi schema database lokal masih berasal dari migration history lama sehingga kolom tersebut belum tersedia.

**Penyelesaian:**

- schema final `letter_types` dikonsolidasikan ke migration pembentukan tabel;
- migration patch untuk penambahan field lama dibersihkan;
- migration history dibuat merepresentasikan schema final secara langsung;
- karena workflow development menggunakan `migrate:fresh`, database lokal dapat dibangun ulang dari schema canonical.

**Catatan:** tidak menggunakan pola tambal-sulam untuk migration final. Migration baru tetap digunakan bila memang ada perubahan arsitektur/schema yang terpisah.

---

### 2.2 Manual Enter pada alamat letterhead hilang

**Gejala:** posisi center letterhead sudah benar, tetapi baris alamat pada hasil PDF tidak mengikuti posisi Enter yang ditulis pada **Alamat Lengkap** tenant.

**Contoh input:**

```text
Jalan Rakumpit Raya, Mungku Baru, Rakumpit, Kota Palangka Raya 73229
Kalimantan Tengah
Telp xx, surel : xxx, laman : xxx
```

**Penyebab:** `DocxLetterheadService::textParagraph()` sebenarnya sudah memecah teks berdasarkan newline dan membangun `$content` menggunakan `<w:br/>`, tetapi return XML masih memasukkan `$text` asli. Dengan demikian `$content` yang sudah benar tidak pernah digunakan.

**Penyelesaian:** return XML sekarang menggunakan `$content`, sehingga manual line break dipertahankan ketika DOCX dibuat.

**Commit:**

```text
3937e46fd8304de7f0233880749f8ed3b00c992f
```

---

### 2.3 Data Pangkat/Golongan hanya tersedia di Administration → Users

**Gejala:**

- halaman Administration → Users sudah memiliki data Pangkat, Golongan, Status Pegawai, Tanggal Masuk, dan Tanggal Pensiun;
- halaman Tenant Admin → Tenant Users sebelumnya hanya memiliki Name, NIP, Email, Role, Password, dan Status.

Akibatnya Tenant Admin tidak dapat mengelola profil kepegawaian user tenant secara lengkap.

**Penyebab:** form Tenant Users belum menghubungkan field ke `employeeProfile`, walaupun `UserService` sudah memiliki dukungan untuk employee profile.

**Penyelesaian:** form Tenant Users diperluas dengan:

- NIP;
- Pangkat;
- Golongan;
- Status Pegawai;
- Tanggal Masuk;
- Tanggal Pensiun.

Data edit sekarang dibaca dari `employeeProfile`, dan data save diteruskan ke `UserService` agar tetap tersimpan pada `employee_profiles`.

**Commit:**

```text
5a93ccf0c3766038b0531ea712b78b2fecdbbddc
```

---

## 3. Fitur baru: Lampiran Surat Keluar

Ditambahkan mekanisme lampiran PDF pada Surat Keluar.

### Struktur data

Tabel baru:

```text
outgoing_letter_attachments
```

Data yang disimpan antara lain:

- UUID;
- `outgoing_letter_id`;
- urutan lampiran;
- judul;
- sumber lampiran;
- file path;
- nama file asli;
- MIME type;
- jumlah halaman;
- ukuran file;
- timestamps.

Urutan lampiran dibuat unik per Surat Keluar.

### Batasan V1

- maksimal 20 lampiran per surat;
- maksimal 20 MB per file;
- hanya PDF;
- setiap file dihitung jumlah halamannya;
- file disimpan per tenant dan per surat.

### Pengelolaan

Tenant User dapat mengelola lampiran selama surat masih berada pada kondisi yang diperbolehkan, termasuk:

- upload;
- memberi/mengubah judul;
- menghapus;
- menaikkan/menurunkan urutan.

Setelah perubahan lampiran, DOCX surat diregenerasi dan preview PDF diperbarui.

---

## 4. Integrasi `{{lampiran}}`

Marker sistem baru:

```text
{{lampiran}}
```

Fungsinya memberikan jumlah halaman lampiran ke template surat.

Contoh hasil:

```text
Lampiran : 3 (tiga) lembar
```

Jika tidak ada lampiran dan paragraph hanya berisi marker lampiran, paragraph dapat dihilangkan agar tidak meninggalkan teks kosong pada surat.

`lampiran` juga sudah dikenali sebagai system/reserved variable oleh sistem template dan renderer.

---

## 5. Integrasi lampiran ke PDF

Lampiran tidak hanya disimpan sebagai file terpisah, tetapi sudah diintegrasikan ke alur dokumen.

### Preview

Untuk surat yang belum diterbitkan:

```text
DOCX
  ↓
PDF utama
  ↓
PDF lampiran
  ↓
PDF gabungan
  ↓
Preview watermark
```

### Penerbitan

Untuk surat yang diterbitkan:

```text
DOCX
  ↓
PDF utama
  ↓
PDF lampiran
  ↓
PDF gabungan
  ↓
TTE / signing
  ↓
Dokumen terbit
```

Dengan demikian tanda tangan digital diterapkan pada paket dokumen yang sudah mencakup lampiran.

### Header lampiran

Pada halaman pertama setiap lampiran ditambahkan header yang memuat konteks surat, antara lain:

- `Lampiran I`, `Lampiran II`, dst.;
- nama surat/tenant;
- nomor surat;
- tanggal;
- hal;
- garis pemisah.

Nomor lampiran menggunakan angka Romawi, sedangkan jumlah halaman menggunakan angka dan terbilang.

---

## 6. Perbaikan arsitektur data User

Data user sekarang dibedakan secara jelas:

```text
users
  ├── identitas/login
  ├── tenant
  ├── status
  └── RBAC

employee_profiles
  ├── NIP
  ├── Pangkat
  ├── Golongan
  ├── Status Pegawai
  ├── Tanggal Masuk
  └── Tanggal Pensiun
```

Ini menghindari duplikasi data kepegawaian pada `users`.

`UserService` sudah menangani create/update employee profile dalam transaction dan mencatat perubahan ke audit log.

---

## 7. Pemisahan data kepegawaian, jabatan, dan RBAC

Kita mempertahankan tiga konsep yang berbeda:

### Data kepegawaian

Contoh:

- Pangkat;
- Golongan;
- Status Pegawai;
- Tanggal Masuk;
- Tanggal Pensiun.

Disimpan pada `employee_profiles`.

### Jabatan

Struktur jabatan tetap dikelola melalui modul/konfigurasi **Positions**. Jabatan tidak diduplikasi sebagai field bebas pada form user jika sumber data jabatan sudah tersedia melalui struktur posisi.

### RBAC

Role/RBAC tetap menjadi sumber kewenangan sistem. Data kepegawaian tidak digunakan sebagai pengganti role/permission.

Prinsipnya:

```text
Employee Profile ≠ Position ≠ RBAC Role
```

---

## 8. Perbaikan Tenant Users

Tenant Admin sekarang dapat mengelola user tenant dengan data yang lebih lengkap tanpa membuka akses administratif lintas tenant.

Form Tenant Users mencakup:

```text
Name
NIP
Pangkat
Golongan
Status Pegawai
Tanggal Masuk
Tanggal Pensiun
Email / Login
Role
Password
Status
```

Role tetap mengikuti aturan RBAC dan pembatasan Tenant Admin. Penambahan field employee profile tidak mengubah model authorization.

---

## 9. Hal yang belum dianggap selesai

Beberapa hal masih perlu hardening sebelum dianggap final:

1. Menambahkan regression test khusus lampiran.
2. Menambahkan test untuk PDF gabungan dan urutan lampiran.
3. Menambahkan test untuk `{{lampiran}}` pada renderer.
4. Menambahkan test untuk manual line break pada letterhead.
5. Meninjau transaksi pada proses upload beberapa lampiran agar kegagalan di tengah proses tidak meninggalkan record/file parsial.
6. Meninjau konsistensi tipe ID user pada beberapa Livewire component agar tidak mengasumsikan ID integer jika model menggunakan UUID.
7. Meninjau kembali validasi MIME PDF agar cukup ketat tetapi tidak menolak PDF valid yang dilaporkan server sebagai MIME generic.
8. Meninjau rendering header lampiran untuk kasus dokumen dengan ukuran/format halaman yang berbeda.
9. Melakukan full regression test setelah seluruh perubahan hari ini berada pada environment development.

---

## 10. Rencana berikutnya

Urutan kerja yang disepakati tetap:

```text
Audit
  ↓
Model / Schema
  ↓
Authorization
  ↓
Service / Business Logic
  ↓
UI
  ↓
Feature / Regression Test
  ↓
Full Regression
```

Prioritas lanjutan:

### Prioritas 1 — Hardening Lampiran

- regression test upload;
- delete;
- reorder;
- jumlah halaman;
- kombinasi PDF utama + lampiran;
- preview;
- issuance/TTE;
- tenant isolation.

### Prioritas 2 — Hardening User & RBAC

- audit ulang matrix Super Admin / Tenant Admin / Tenant User;
- pastikan data employee profile hanya dapat diubah oleh actor yang berwenang;
- pastikan role dan permission tidak dapat dimanipulasi melalui request langsung;
- pastikan Tenant Admin tidak dapat keluar dari tenant scope.

### Prioritas 3 — Historical Integrity

Pastikan perubahan template, TND, employee profile, role, dan konfigurasi baru tidak mengubah dokumen Surat Keluar historis.

### Prioritas 4 — Dynamic Letter Form / Snapshot

Melanjutkan roadmap TND yang sudah ada setelah fondasi Surat Keluar stabil:

```text
Dynamic Letter Form
        ↓
Historical Snapshot
        ↓
Signing Rules
        ↓
Hardening
```

---

## 11. Catatan development

Untuk migration yang sudah dikonsolidasikan, workflow development tetap dapat menggunakan:

```bash
php artisan migrate:fresh --seed
```

Untuk perubahan UI/service seperti Tenant Users dan letterhead, tidak diperlukan `migrate:fresh` selama tidak ada perubahan schema.

Setelah `git pull`, perubahan letterhead perlu diuji dengan membuat/regenerasi dokumen baru karena file DOCX/PDF yang sudah pernah dihasilkan sebelumnya tidak otomatis berubah.

---

## 12. Checkpoint teknis

Commit penting yang tercatat hari ini:

```text
Letterhead manual line break:
3937e46fd8304de7f0233880749f8ed3b00c992f

Tenant Users employee profile fields:
5a93ccf0c3766038b0531ea712b78b2fecdbbddc
```

Checkpoint ini menjadi referensi sebelum melanjutkan audit dan hardening berikutnya.
