# DANUM — Proses Bisnis Saat Ini

**Status:** Proses bisnis aktif hasil implementasi bertahap  
**Scope:** Tahap awal DANUM, terutama Surat Keluar  
**Tanggal dokumentasi:** 25 Agustus 2026

Dokumen ini menjadi ringkasan proses bisnis yang **sudah dibangun dan disepakati selama pengembangan**, bukan daftar fitur hipotetis. Detail arsitektur tetap dirujuk dari dokumen TND lainnya di folder `docs/`.

---

## 1. Prinsip Bisnis Utama

DANUM menggunakan prinsip:

> **TND dikelola secara terpusat, digunakan secara terdistribusi.**

Kompleksitas TND dikelola oleh pihak yang berwenang, sedangkan user OPD mendapatkan pengalaman penggunaan yang sederhana.

User OPD tidak perlu memahami:

- klasifikasi TND secara teknis;
- template yang harus dipilih;
- versi template;
- font dan margin;
- struktur layout;
- aturan konfigurasi internal.

User cukup menjawab kebutuhan bisnis:

> **"Saya ingin membuat surat apa?"**

Sistem yang menentukan:

> **"Apakah unit ini boleh membuatnya, dan konfigurasi surat mana yang berlaku?"**

Prinsip ini merupakan dasar desain arsitektur DANUM. fileciteturn451file0L2-L2

---

## 2. Aktor dan Tanggung Jawab

### 2.1 Super Admin

Super Admin merupakan administrator sistem dengan akses lintas tenant untuk kebutuhan administrasi sistem.

Tindakan administratif yang wajib dicatat harus tetap melalui audit trail. Super Admin tidak boleh menggunakan akses tinggi sebagai alasan untuk melewati pencatatan.

### 2.2 Administrator TND / Ortal

Bertanggung jawab terhadap konfigurasi TND, antara lain:

- jenis surat;
- template;
- versi template;
- masa berlaku konfigurasi;
- kewenangan unit terhadap jenis surat;
- konfigurasi pendukung yang relevan;
- riwayat perubahan konfigurasi.

Konsep Administrator TND/Ortal tidak otomatis identik dengan role Super Admin; pemisahan kewenangan dapat berkembang sesuai kebutuhan. fileciteturn451file0L2-L2

### 2.3 Tenant Admin

Bertanggung jawab atas administrasi operasional tenant/unit sesuai authorization.

### 2.4 Tenant User / User OPD

User operasional yang:

- melihat jenis surat yang tersedia untuk unitnya;
- membuat draft Surat Keluar;
- mengisi data;
- melakukan preview;
- mengirim surat ke proses berikutnya;
- melihat surat unitnya;
- memperbaiki surat jika dikembalikan/rejected sesuai aturan workflow.

User OPD tidak boleh:

- mengubah template resmi;
- mengubah format TND;
- mengubah kop/struktur resmi;
- mengaktifkan jenis surat;
- memberikan permission jenis surat kepada unit lain.

---

## 3. Kewenangan Jenis Surat

Kewenangan jenis surat merupakan data/configuration, bukan hard-code.

Model bisnis:

```text
Unit / Tenant
      |
      v
Permission Jenis Surat
      |
      v
Jenis Surat
      |
      v
Template / Version
```

### Contoh

```text
Kelurahan
  ├── Surat Dinas       -> diizinkan
  ├── Surat Undangan    -> diizinkan
  ├── Surat Keterangan  -> diizinkan
  └── Surat Tugas       -> tidak diizinkan
```

Jika jenis surat tidak diberikan kepada unit:

1. jenis surat tidak ditampilkan di UI **Buat Surat**;
2. request langsung/manipulasi endpoint tetap ditolak oleh backend.

Jadi UI filtering adalah pengalaman pengguna, sedangkan backend authorization adalah enforcement sebenarnya.

---

## 4. Proses Buat Surat

Alur bisnis user OPD:

```text
Login
  |
  v
Surat Keluar
  |
  v
Buat Surat
  |
  v
Sistem membaca unit/tenant user
  |
  v
Sistem mengambil jenis surat yang diizinkan
  |
  v
User memilih jenis surat
  |
  v
Sistem menentukan template/configuration yang berlaku
  |
  v
Form surat
  |
  v
User mengisi data
  |
  v
Preview
  |
  v
Simpan sebagai Draft
```

User tidak memilih template secara bebas.

---

## 5. Template dan Versioning

Template menentukan bagaimana surat ditampilkan, sedangkan data surat menentukan apa yang ditulis dalam surat.

### Template mengatur

- kop;
- margin;
- font;
- ukuran font;
- posisi elemen;
- struktur surat;
- footer;
- tanda tangan;
- layout resmi lainnya.

### User mengisi data

Contohnya:

- nomor/dasar sesuai field yang tersedia;
- nama;
- jabatan;
- tujuan;
- waktu;
- tempat;
- keperluan;
- data lain yang dibutuhkan jenis surat.

### Versioning

Template memiliki versi dan periode berlaku.

Contoh:

```text
Surat Tugas v1
  berlaku Januari 2025 - Juli 2026

Surat Tugas v2
  berlaku mulai Agustus 2026
```

Ketika versi baru aktif:

- surat baru menggunakan versi yang berlaku;
- surat lama tetap mempertahankan konfigurasi/version yang menjadi dasar historisnya;
- perubahan template tidak boleh mengubah dokumen historis secara otomatis.

Detail versioning tersedia pada `docs/03-template-versioning-dan-snapshot.md`.

---

## 6. Workflow Surat Keluar

Workflow utama saat ini:

```text
DRAFT
  |
  | Submit
  v
SUBMITTED
  |
  +------------------+
  |                  |
  | Reject           | Validate
  v                  v
DRAFT             VALIDATED
                      |
                      +-------------+
                      |             |
                      | Reject      | Issue
                      v             v
                    DRAFT         ISSUED
```

Transition penting harus diperiksa di backend.

---

## 7. Draft

`DRAFT` merupakan kondisi surat yang masih dikerjakan oleh pembuat sesuai authorization.

Pada tahap ini user dapat memperbaiki data surat selama aturan status/policy mengizinkan.

Draft belum boleh dianggap sebagai dokumen yang telah diterbitkan atau dokumen publik yang sah.

---

## 8. Submit

Submit berarti pembuat mengajukan surat ke tahap workflow berikutnya.

Setelah submit, surat masuk ke proses verifikasi dan aturan edit mengikuti status/policy.

Tujuan submit adalah memisahkan surat yang masih dikerjakan dengan surat yang sudah diajukan untuk keputusan pihak berikutnya.

---

## 9. Verifikasi

Verifikasi hanya dapat dilakukan oleh user yang memang ditunjuk sebagai verifikator dan memenuhi authorization backend.

### Catatan wajib

Verifikator **wajib memberikan catatan** sebelum melakukan validasi.

Tidak boleh terjadi:

```text
Klik Verifikasi
      |
      v
VALIDATED
```

Tanpa evidence.

Alur yang benar:

```text
Klik Verifikasi
      |
      v
Modal Catatan Verifikasi
      |
      v
Catatan wajib diisi
      |
      v
Simpan & Lanjutkan
      |
      v
VALIDATED
```

Catatan verifikasi disimpan pada history event sehingga menjadi bagian dari bukti historis workflow.

---

## 10. Penolakan

Verifikator/pihak yang memiliki kewenangan reject dapat mengembalikan surat ke tahap yang sesuai menurut workflow.

Penolakan wajib memiliki alasan/catatan.

Contoh:

```text
SUBMITTED
   |
   | Reject + alasan
   v
DRAFT
```

Jika surat diperbaiki lalu dikirim kembali, history tidak dihapus.

Contoh histori:

```text
Created
Submitted
Rejected + catatan A
Submitted
Validated + catatan B
```

Dengan demikian proses perbaikan tetap dapat ditelusuri.

---

## 11. Penerbitan / Penandatanganan

Penerbitan hanya boleh dilakukan oleh user yang ditunjuk sebagai penanda tangan dan memenuhi authorization backend.

### Catatan wajib

Penanda tangan **wajib memberikan catatan** sebelum menerbitkan surat.

Alur:

```text
VALIDATED
    |
    v
Klik Issue / Terbitkan
    |
    v
Modal Catatan Penandatanganan
    |
    v
Catatan wajib diisi
    |
    v
Simpan & Lanjutkan
    |
    v
ISSUED
```

Penerbitan bukan sekadar perubahan status. Pada titik ini sistem harus menjaga historical integrity, termasuk data penerbitan dan verification token sesuai business rule.

---

## 12. Status dan Masa Berlaku

Surat yang sudah `ISSUED` dapat memiliki masa berlaku.

Secara konseptual:

```text
ISSUED + belum mulai berlaku
    -> NOT_YET_ACTIVE

ISSUED + masih dalam periode berlaku
    -> ACTIVE

ISSUED + melewati valid_until
    -> EXPIRED

WITHDRAWN
    -> WITHDRAWN
```

`expired` merupakan hasil perhitungan masa berlaku dan tidak harus menjadi pengganti status database `ISSUED`.

---

## 13. Withdrawal

Surat yang sudah diterbitkan dapat masuk proses penarikan sesuai business rule.

Alur:

```text
ISSUED
   |
   v
Withdrawal Request
   |
   +----> APPROVED -> WITHDRAWN
   |
   +----> REJECTED -> tetap ISSUED
```

Request/decision harus menyimpan actor, waktu, dan alasan/catatan yang diwajibkan.

Detail workflow withdrawal dirujuk pada `docs/04-workflow-surat-keluar.md`.

---

## 14. Status History

Setiap perubahan lifecycle penting dicatat sebagai history.

History berfungsi sebagai kronologi, bukan pengganti current state.

Model konseptual:

```text
OutgoingLetter
   |
   +---- current status
   |
   +---- Status History[]
              |
              +-- status
              +-- action
              +-- actor
              +-- timestamp
              +-- note
```

Catatan workflow disimpan langsung pada event history.

Ini penting untuk mencegah kasus seperti:

```text
Verifikasi #1 -> catatan A
Tolak         -> catatan B
Submit ulang
Verifikasi #2 -> catatan C
```

Semua catatan tetap menjadi histori dan tidak tertimpa oleh nilai current surat.

---

## 15. Audit Log

Audit Log digunakan untuk aktivitas administratif dan perubahan data penting.

Informasi yang ditelusuri mencakup, sesuai object/action yang dicatat:

- actor;
- tenant;
- action;
- object/model;
- object ID;
- before;
- after;
- timestamp;
- IP address;
- user-agent.

Contoh:

```text
Actor: DANUM Admin
Tenant: Kelurahan Mungku Baru
Action: letter_type.permission.granted
Object: LetterTypePermission
Before: allowed = false
After:  allowed = true
```

Audit Log bersifat administratif dan berbeda fungsi dengan status history Surat Keluar:

```text
Status History -> kronologi lifecycle satu surat
Audit Log      -> aktivitas/perubahan data sistem yang dapat diaudit
```

---

## 16. Public Verification

Setelah surat `ISSUED`, sistem memiliki verification token yang dapat digunakan oleh halaman publik.

Public verification tidak boleh membocorkan data administratif yang tidak diperlukan.

State yang dibedakan:

```text
Unknown / unissued token
    -> tidak terverifikasi

ISSUED + active
    -> dokumen valid

ISSUED + expired
    -> dokumen pernah diterbitkan, tetapi sudah kedaluwarsa

ISSUED + future valid_from
    -> dokumen belum mulai berlaku

WITHDRAWN
    -> dokumen telah ditarik
```

Dokumen yang belum diterbitkan tidak boleh tampil sebagai dokumen publik yang sah.

---

## 17. Realtime / Multi-browser Update

Workflow mendukung pembaruan state dari browser lain melalui mekanisme refresh Livewire/polling yang sudah diterapkan.

Contoh:

```text
Browser 1 — Verifikator
    |
    | Verify + catatan
    v
Database
    |
    v
Browser 2 — Admin / requester
    |
    +--> status terbaru
    +--> history terbaru
    +--> note terbaru
```

Tujuannya agar user tidak perlu melakukan full page reload secara manual untuk melihat perubahan workflow.

---

## 18. Authorization dan Security

Prinsip utama:

> **Menyembunyikan tombol bukan authorization.**

Contoh:

Jika seorang user bukan verifikator:

- tombol Verifikasi boleh tidak ditampilkan;
- tetapi jika request dipanggil langsung, backend tetap harus menolak.

Demikian pula untuk:

- jenis surat yang tidak diizinkan;
- Issue oleh user yang bukan signer;
- akses tenant lain;
- transition status yang tidak sah;
- akses data administratif Super Admin.

Semua business rule penting harus ditegakkan di backend.

---

## 19. Historical Integrity

DANUM harus menjaga agar data historis tidak berubah secara diam-diam.

Contoh yang harus dipertahankan:

```text
Surat dibuat dengan Template v1
        |
Template v2 diaktifkan
        |
Surat lama tetap merepresentasikan v1
```

Hal yang sama berlaku untuk workflow history dan catatan keputusan.

---

## 20. Prinsip UI/UX yang Sudah Ditetapkan

### Untuk user biasa

- sederhana;
- tidak menampilkan kompleksitas TND;
- pilihan hanya yang berwenang;
- modal konfirmasi menggunakan komponen UI DANUM, bukan browser `alert/confirm` bila sudah tersedia pola komponen internal;
- pagination konsisten dengan desain pagination yang sudah digunakan;
- status dan workflow harus mudah dipahami.

### Untuk workflow decision

Action penting menggunakan modal yang meminta evidence ketika diperlukan:

```text
Verifikasi -> catatan wajib
Tolak      -> alasan wajib
Issue      -> catatan wajib
```

---

## 21. Testing sebagai bagian dari Proses Bisnis

Business rule tidak dianggap selesai hanya karena method berhasil dipanggil.

Regression test harus memastikan:

- actor yang salah ditolak;
- tenant isolation tetap berlaku;
- jenis surat yang tidak berwenang ditolak;
- transition status tidak sah ditolak;
- catatan wajib tidak dapat dilewati;
- catatan tersimpan pada history yang benar;
- audit log tercatat;
- public verification hanya menampilkan state yang sesuai;
- realtime refresh tidak merusak state UI.

Checkpoint pengembangan yang terdokumentasi saat file ini dibuat adalah **103 test passed**.

---

## 22. Batas Scope Saat Ini

Fokus tahap awal DANUM adalah **Surat Keluar**.

Modul berikut belum menjadi fokus utama tahap awal kecuali sebagai fondasi/roadmap:

- Surat Masuk;
- Disposisi;
- Arsip umum;
- workflow persuratan lain di luar lifecycle Surat Keluar.

Jangan memperluas scope hanya demi menambah banyak menu. Setiap modul baru harus memiliki business rule yang jelas, authorization yang jelas, dan regression test yang memadai.

---

## 23. Referensi Dokumentasi

- `docs/01-arsitektur-tnd.md` — arsitektur TND.
- `docs/02-model-tnd-dan-kewenangan.md` — model TND dan kewenangan.
- `docs/03-template-versioning-dan-snapshot.md` — versioning dan snapshot.
- `docs/04-workflow-surat-keluar.md` — lifecycle Surat Keluar.
- `docs/05-testing-strategy.md` — strategi testing.
- `docs/06-roadmap-tnd.md` — roadmap TND.
- `docs/07-super-admin-dan-break-glass-access.md` — Super Admin dan break-glass access.
- `docs/08-implementasi-template-versioning.md` — implementasi template versioning.
- `docs/UI_ERROR_CONVENTION.md` — konvensi error UI.
