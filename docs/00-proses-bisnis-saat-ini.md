# DANUM — Proses Bisnis Saat Ini

**Status:** Proses bisnis aktif hasil implementasi bertahap  
**Scope:** Tahap awal, terutama Surat Keluar  
**Tanggal:** 25 Agustus 2026

Dokumen ini merangkum proses bisnis yang sudah dibangun dan disepakati selama pengembangan DANUM. Detail arsitektur dan implementasi tetap dijelaskan pada dokumen lain di folder `docs/`.

## 1. Prinsip Utama

DANUM menerapkan prinsip **TND dikelola secara terpusat, digunakan secara terdistribusi**.

Kompleksitas TND dikelola oleh pihak yang berwenang, sedangkan user OPD mendapatkan pengalaman sederhana. User cukup memilih kebutuhan surat; sistem menentukan kewenangan unit dan konfigurasi/template yang berlaku.

```text
User OPD
   |
   v
Jenis Surat yang tersedia untuk unit
   |
   v
Template / Version yang berlaku
   |
   v
Surat Keluar
```

## 2. Aktor

### Super Admin

Administrator sistem dengan akses lintas tenant untuk kebutuhan administrasi. Tindakan administratif yang wajib dicatat tetap melalui audit trail.

### Administrator TND / Ortal

Mengelola konfigurasi TND, termasuk jenis surat, template, versioning, kewenangan unit, dan riwayat perubahan.

### Tenant Admin

Mengelola kebutuhan operasional tenant sesuai authorization.

### Tenant User / User OPD

Membuat, mengisi, preview, dan memproses Surat Keluar sesuai kewenangan unit.

### Verifikator

User yang ditunjuk untuk memeriksa surat. Verifikasi wajib disertai catatan.

### Penanda tangan

User yang ditunjuk untuk menerbitkan/menandatangani surat. Penerbitan wajib disertai catatan.

## 3. Kewenangan Jenis Surat

Kewenangan jenis surat adalah configuration, bukan hard-code.

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

Jika suatu unit tidak memiliki akses:

1. jenis surat tidak ditampilkan pada **Buat Surat**;
2. backend tetap menolak request langsung/manipulasi endpoint.

Menyembunyikan pilihan di UI bukan pengganti authorization backend.

## 4. Proses Buat Surat

```text
Login
  |
Surat Keluar
  |
Buat Surat
  |
Sistem membaca unit/tenant
  |
Sistem mengambil jenis surat yang diizinkan
  |
User memilih jenis surat
  |
Sistem menentukan template/configuration
  |
Form surat
  |
User mengisi data
  |
Preview
  |
Simpan sebagai Draft
```

User OPD tidak memilih template, versi, font, margin, kop, atau layout secara bebas.

## 5. Template dan Versioning

Template menentukan tampilan resmi; data surat menentukan isi.

Template dapat mengatur:

- kop;
- margin;
- font dan ukuran;
- posisi elemen;
- struktur surat;
- footer;
- tanda tangan;
- layout resmi.

Template memiliki versi dan periode berlaku.

```text
Template v1 -> periode lama
Template v2 -> mulai berlaku pada tanggal tertentu
```

Versi baru digunakan untuk surat baru sesuai configuration. Surat historis tetap menggunakan konfigurasi/version yang menjadi dasar dokumennya dan tidak berubah otomatis hanya karena template baru aktif.

## 6. Workflow Surat Keluar

```text
DRAFT
  |
  | Submit
  v
SUBMITTED
  |
  +---- Reject + alasan ----> DRAFT
  |
  | Validate + catatan
  v
VALIDATED
  |
  +---- Reject + alasan ----> DRAFT
  |
  | Issue + catatan signer
  v
ISSUED
```

Semua transition penting harus ditegakkan di backend.

## 7. Draft

`DRAFT` adalah surat yang masih dapat dikerjakan sesuai authorization. Draft belum merupakan dokumen yang diterbitkan atau dokumen publik yang sah.

## 8. Submit

Submit berarti pembuat mengajukan draft ke proses berikutnya. Setelah submit, aturan edit mengikuti status dan policy workflow.

## 9. Verifikasi

Verifikasi hanya boleh dilakukan oleh verifikator yang ditunjuk dan berwenang.

**Catatan verifikasi wajib.** Alurnya:

```text
Klik Verifikasi
      |
Modal Catatan Verifikasi
      |
Catatan wajib diisi
      |
Simpan & Lanjutkan
      |
VALIDATED
```

Tidak ada validasi yang sah tanpa evidence note.

## 10. Penolakan

Penolakan wajib memiliki alasan/catatan.

Contoh:

```text
SUBMITTED
   |
   | Reject + alasan
   v
DRAFT
```

Jika surat diperbaiki dan disubmit kembali, history lama tidak dihapus.

## 11. Penerbitan / Penandatanganan

Issue hanya boleh dilakukan oleh signer yang ditunjuk dan berwenang.

**Catatan penandatanganan wajib.** Alurnya:

```text
VALIDATED
    |
Klik Issue
    |
Modal Catatan Penandatanganan
    |
Catatan wajib diisi
    |
Simpan & Lanjutkan
    |
ISSUED
```

Penerbitan menjaga historical integrity dan menghasilkan data yang diperlukan untuk public verification sesuai business rule.

## 12. Masa Berlaku

Surat `ISSUED` dapat memiliki masa berlaku.

```text
ISSUED + future valid_from -> NOT_YET_ACTIVE
ISSUED + masih berlaku      -> ACTIVE
ISSUED + melewati valid_until -> EXPIRED
WITHDRAWN                   -> WITHDRAWN
```

`EXPIRED` merupakan hasil perhitungan masa berlaku dan tidak harus mengganti current database status `ISSUED`.

## 13. Withdrawal

Surat yang sudah diterbitkan dapat masuk proses penarikan.

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

Request dan keputusan menyimpan actor, waktu, serta alasan/catatan sesuai business rule.

## 14. Status History

Status history merupakan kronologi lifecycle satu surat.

```text
OutgoingLetter
   |
   +-- current status
   |
   +-- Status History[]
          |
          +-- status
          +-- action
          +-- actor
          +-- timestamp
          +-- note
```

Catatan workflow disimpan langsung pada event history agar tidak hilang ketika field current surat berubah.

Contoh:

```text
Created
Submitted
Rejected + catatan A
Submitted
Validated + catatan B
Issued + catatan C
```

## 15. Audit Log

Audit Log digunakan untuk aktivitas administratif dan perubahan data penting.

Informasi yang dapat dicatat:

- actor;
- tenant;
- action;
- object/model dan ID;
- before;
- after;
- timestamp;
- IP address;
- user-agent.

Status History dan Audit Log berbeda fungsi:

```text
Status History -> kronologi lifecycle satu surat
Audit Log      -> aktivitas/perubahan data sistem yang dapat diaudit
```

## 16. Public Verification

Surat yang sudah `ISSUED` memiliki verification token untuk pemeriksaan publik.

```text
Unknown / unissued -> tidak terverifikasi
ISSUED + active    -> valid
ISSUED + expired   -> pernah diterbitkan, tetapi kedaluwarsa
ISSUED + future    -> belum mulai berlaku
WITHDRAWN          -> telah ditarik
```

Surat yang belum diterbitkan tidak boleh dianggap sebagai dokumen publik yang sah.

## 17. Realtime / Multi-browser

Detail workflow mendukung pembaruan state dari browser lain melalui event Livewire/polling.

```text
Browser Verifikator
       |
       | Verify / Reject / Issue
       v
Database
       |
       v
Browser Admin / requester
       |
       +--> status terbaru
       +--> history terbaru
       +--> note terbaru
```

Tujuannya agar user tidak harus melakukan full page reload manual untuk melihat perubahan workflow.

## 18. Authorization dan Security

Prinsip utama:

> **Menyembunyikan tombol bukan authorization.**

Backend harus menolak:

- jenis surat yang tidak diizinkan;
- transition status yang tidak sah;
- verifikasi oleh user yang bukan verifier;
- issue oleh user yang bukan signer;
- akses tenant lain;
- akses administratif yang tidak sesuai role/policy.

## 19. Historical Integrity

Data historis tidak boleh berubah diam-diam.

```text
Surat dibuat dengan Template v1
        |
Template v2 diaktifkan
        |
Surat lama tetap merepresentasikan v1
```

Prinsip yang sama berlaku untuk status history, catatan keputusan, dan audit trail.

## 20. Prinsip UI/UX

Untuk user biasa:

- UI sederhana;
- kompleksitas TND disembunyikan;
- hanya pilihan yang berwenang yang ditampilkan;
- action penting memakai modal internal DANUM;
- pagination konsisten;
- status workflow mudah dipahami.

Untuk decision workflow:

```text
Verifikasi -> catatan wajib
Tolak      -> alasan wajib
Issue      -> catatan wajib
```

## 21. Testing

Business rule dianggap selesai jika workflow dan regression test-nya aman, bukan hanya jika method dapat dipanggil.

Regression test harus mencakup antara lain:

- authorization actor;
- tenant isolation;
- permission jenis surat;
- valid transition;
- catatan wajib;
- history note persistence;
- audit log;
- public verification;
- realtime refresh.

Checkpoint pengembangan pada saat dokumentasi ini dibuat: **103 test passed**.

## 22. Scope Tahap Awal

Fokus tahap awal DANUM adalah **Surat Keluar**.

Surat Masuk, Disposisi, Arsip umum, dan workflow persuratan lain berada di luar fokus tahap awal sampai business rule dan roadmap-nya ditetapkan.

## 23. Referensi

- `docs/01-arsitektur-tnd.md`
- `docs/02-model-tnd-dan-kewenangan.md`
- `docs/03-template-versioning-dan-snapshot.md`
- `docs/04-workflow-surat-keluar.md`
- `docs/05-testing-strategy.md`
- `docs/06-roadmap-tnd.md`
- `docs/07-super-admin-dan-break-glass-access.md`
- `docs/08-implementasi-template-versioning.md`
- `docs/UI_ERROR_CONVENTION.md`
