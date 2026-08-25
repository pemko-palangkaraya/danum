# DANUM

**DANUM** adalah platform persuratan pemerintah daerah yang menerapkan Tata Naskah Dinas (TND) secara terpusat dalam konfigurasi, tetapi digunakan secara terdistribusi oleh OPD/unit kerja.

> **Prinsip utama:** kompleksitas TND dikelola oleh administrator TND/Ortal; pengalaman pengguna OPD dibuat sederhana.

## Status proyek

Tahap aktif saat ini berfokus pada **Surat Keluar** beserta pengaturan jenis surat, kewenangan unit, workflow verifikasi/penerbitan, public verification, withdrawal, dan audit trail.

Checkpoint pengembangan terakhir: **103 test passed**.

## Tujuan

DANUM dirancang agar:

- jenis surat dan aturan TND dikelola secara terpusat;
- OPD hanya melihat jenis surat yang memang menjadi kewenangannya;
- template dan format resmi tidak diubah oleh user OPD;
- perubahan template menggunakan versioning dan tidak mengubah dokumen historis;
- proses Surat Keluar memiliki workflow yang terkontrol;
- setiap tindakan penting dapat ditelusuri melalui history dan audit log;
- dokumen yang sudah diterbitkan dapat diverifikasi secara publik;
- authorization diterapkan di backend, bukan hanya dengan menyembunyikan tombol/menu.

## Aktor utama

### Super Admin

Administrator sistem dengan akses lintas tenant untuk kebutuhan administrasi sistem. Akses administratif dan tindakan penting harus tetap dapat diaudit.

### Administrator TND / Ortal

Pengelola konfigurasi TND: jenis surat, template, versi template, kewenangan unit, dan konfigurasi terkait.

> Implementasi role/permission dapat berkembang. Jangan menyamakan konsep Administrator TND/Ortal dengan Super Admin secara otomatis.

### Tenant Admin

Administrator operasional pada tenant/unit untuk pengelolaan pengguna dan kebutuhan operasional tenant sesuai authorization.

### Tenant User / User OPD

Pengguna operasional yang membuat dan memproses Surat Keluar sesuai kewenangan unitnya.

### Verifikator

User yang ditunjuk pada surat untuk melakukan verifikasi. Verifikasi wajib disertai catatan.

### Penanda tangan

User yang ditunjuk pada surat untuk melakukan penerbitan/penandatanganan. Penerbitan wajib disertai catatan.

## Konsep inti

```text
Tenant / Unit
      |
      +---- Users / Roles
      |
      +---- Letter Type
               |
               +---- Permission per Unit
               |
               +---- Template
                       |
                       +---- Template Version
                               |
                               v
                         Outgoing Letter
                               |
                +--------------+--------------+
                |                             |
           Verification                   Issuing
                |                             |
                +-------------+---------------+
                              |
                         Audit / History
                              |
                    Public Verification
```

Aturan kewenangan harus bersifat **configuration-driven**, bukan hard-code berdasarkan nama tenant atau jenis organisasi.

## Workflow Surat Keluar

Alur utama:

```text
DRAFT
  |
  v
SUBMITTED
  |
  +----> REJECTED -> DRAFT
  |
  v
VALIDATED
  |
  +----> REJECTED -> DRAFT
  |
  v
ISSUED
```

Penerbitan dan verifikasi memiliki actor yang ditentukan. Backend tetap memeriksa actor walaupun UI menyembunyikan tombol untuk user yang tidak berwenang.

### Catatan wajib

- Verifikator wajib memberikan **catatan verifikasi** sebelum validasi.
- Penanda tangan wajib memberikan **catatan penandatanganan** sebelum penerbitan.
- Penolakan wajib memiliki alasan/catatan.
- Catatan workflow disimpan pada status history agar menjadi bukti historis yang tidak hilang ketika field current surat berubah.

## Template dan versioning

User OPD tidak memilih format teknis template secara bebas. Sistem menentukan template berdasarkan jenis surat dan konfigurasi yang berlaku.

Contoh:

```text
Surat Undangan v1 -> berlaku sampai 31 Agustus 2026
Surat Undangan v2 -> berlaku mulai 1 September 2026
```

Surat baru menggunakan versi yang berlaku pada saat pembuatan sesuai business rule. Surat historis tetap menggunakan konfigurasi/version yang menjadi dasar dokumen tersebut.

## Permission jenis surat

Model penggunaan:

```text
User
  -> Role / Authorization
  -> Tenant / Unit
  -> Letter Type Permission
  -> Letter Type
```

Jika suatu unit tidak memiliki akses ke jenis surat, jenis tersebut tidak ditampilkan pada form **Buat Surat** dan backend juga harus menolak manipulasi request langsung.

## Audit trail

Audit Log digunakan untuk aktivitas administratif dan perubahan data penting. Informasi yang ditelusuri dapat mencakup:

- actor;
- tenant;
- action;
- object/model dan ID;
- before;
- after;
- timestamp;
- IP address;
- user-agent.

Super Admin tidak boleh bypass audit trail untuk tindakan yang memang wajib dicatat.

## Public verification

Surat yang telah diterbitkan memiliki verification token. Public verification digunakan untuk memeriksa status dokumen tanpa memberikan akses ke area administratif.

Secara konseptual:

```text
ISSUED + aktif      -> valid
ISSUED + expired    -> expired
ISSUED + future     -> not yet active
WITHDRAWN           -> withdrawn
unknown/unissued    -> not verified
```

Dokumen yang belum diterbitkan tidak boleh dianggap sebagai dokumen publik yang sah.

## Realtime update

Halaman workflow/detail Surat Keluar mendukung refresh state melalui event Livewire/polling sehingga perubahan dari browser/user lain dapat muncul tanpa reload manual penuh.

Contoh:

```text
Browser Verifikator
      |
      | Verifikasi + catatan
      v
Database
      |
      v
Browser Admin / requester
      |
      +--> status + history diperbarui
```

## Tech stack

- PHP `^8.2`
- Laravel `^12.0`
- Livewire `^4.4`
- Livewire Volt `^1.11`
- Tailwind CSS `^4`
- Vite `^7`
- PHPUnit `^11`
- Dompdf untuk PDF
- QR Code untuk verification QR
- FPDI/FPDF untuk kebutuhan PDF

## Instalasi lokal

Persyaratan minimal: PHP 8.2+, Composer, Node.js/npm, dan database yang sesuai konfigurasi `.env`.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Atau gunakan script setup yang tersedia:

```bash
composer run setup
```

## Menjalankan development

```bash
composer run dev
```

Atau secara terpisah:

```bash
php artisan serve
npm run dev
```

## Testing

Jalankan seluruh test suite:

```bash
php artisan test
```

atau:

```bash
composer test
```

Setiap perubahan business rule harus disertai regression test yang relevan.

## Struktur dokumentasi

Dokumentasi proses dan arsitektur berada di [`docs/`](docs/).

Dokumen utama:

1. [`docs/00-proses-bisnis-saat-ini.md`](docs/00-proses-bisnis-saat-ini.md) — proses bisnis yang sudah dibangun dan aturan workflow aktif.
2. [`docs/01-arsitektur-tnd.md`](docs/01-arsitektur-tnd.md) — arsitektur TND.
3. [`docs/02-model-tnd-dan-kewenangan.md`](docs/02-model-tnd-dan-kewenangan.md) — model TND dan permission unit.
4. [`docs/03-template-versioning-dan-snapshot.md`](docs/03-template-versioning-dan-snapshot.md) — template versioning dan historical integrity.
5. [`docs/04-workflow-surat-keluar.md`](docs/04-workflow-surat-keluar.md) — lifecycle Surat Keluar.
6. [`docs/05-testing-strategy.md`](docs/05-testing-strategy.md) — strategi pengujian.
7. [`docs/06-roadmap-tnd.md`](docs/06-roadmap-tnd.md) — roadmap TND.
8. [`docs/07-super-admin-dan-break-glass-access.md`](docs/07-super-admin-dan-break-glass-access.md) — Super Admin dan akses khusus.
9. [`docs/08-implementasi-template-versioning.md`](docs/08-implementasi-template-versioning.md) — catatan implementasi versioning.
10. [`docs/UI_ERROR_CONVENTION.md`](docs/UI_ERROR_CONVENTION.md) — konvensi error UI.

## Prinsip pengembangan

1. Backend boleh kompleks, UI user harus sederhana.
2. Authorization harus ditegakkan di backend.
3. Jangan membuat bypass khusus di UI yang tidak didukung business rule.
4. Tenant isolation harus selalu dipertahankan.
5. TND harus configuration-driven.
6. Template dan data surat harus dipisahkan.
7. Dokumen historis tidak boleh berubah karena konfigurasi baru.
8. Workflow penting harus dapat diaudit.
9. Catatan keputusan penting harus disimpan sebagai evidence.
10. Setiap perubahan workflow harus diikuti regression test.
11. Hindari overengineering dan jangan menambahkan klasifikasi TND yang belum diperlukan.

## Scope tahap berikutnya

Tahap awal tetap berfokus pada Surat Keluar. Modul seperti Surat Masuk, Disposisi, dan Arsip umum dapat dikembangkan kemudian setelah kebutuhan dan business rule ditetapkan.
