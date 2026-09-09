# DANUM — Panduan Operasional Windows, Nginx, Runner & Deployment

> Dokumen ini menjadi buku saku operasional untuk menjalankan DANUM pada laptop Windows lokal, termasuk Nginx, PHP-CGI, Laravel Scheduler, Queue Worker, GitHub Actions self-hosted runner, Task Scheduler, serta alur deployment dan backup database.

## 1. Lingkungan DANUM

DANUM berjalan langsung pada Windows laptop, bukan pada server Linux.

Komponen utama:

| Komponen | Lokasi / Nilai |
|---|---|
| Repository | `C:\Users\yudhistira\Herd\danum` |
| Git | `C:\Program Files\Git\cmd\git.exe` |
| PHP | `C:\Users\yudhistira\.config\herd\bin\php84\php.exe` |
| PHP-CGI | `C:\Users\yudhistira\.config\herd\bin\php84\php-cgi.exe` |
| Composer | `C:\ProgramData\ComposerSetup\bin\composer` |
| Node.js | `C:\Program Files\nodejs\node.exe` |
| Nginx | `C:\nginx` |
| Nginx config | `C:\nginx\conf\nginx.conf` |
| PostgreSQL | `C:\Program Files\PostgreSQL\18\bin\psql.exe` |
| PostgreSQL version | 18.6 |
| PHP version | 8.4.24 |
| Node.js version | 26.7.0 |
| Composer version | 2.10.2 |
| Git version | 2.55.0.windows.4 |
| Host DANUM | `danum.metro` |
| HTTP | Port `80` |
| PHP-CGI | `127.0.0.1:9000` |

## 2. Arsitektur Runtime Lokal

Alur request web:

```text
Browser
  ↓
danum.metro:80
  ↓
Nginx
  ↓
PHP-CGI :9000
  ↓
Laravel DANUM
  ↓
PostgreSQL
```

Proses background:

```text
Laravel Scheduler
  └─ php artisan schedule:work

Laravel Queue Worker
  └─ php artisan queue:work database --queue=default --tries=3 --timeout=900 -vvv
```

## 3. Konfigurasi Nginx

Nginx berada di:

```text
C:\nginx
```

Konfigurasi berada di:

```text
C:\nginx\conf\nginx.conf
```

Konfigurasi penting yang digunakan:

```nginx
server {
    listen 80;
    server_name danum.metro;

    root C:/Users/yudhistira/Herd/danum/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }
}
```

Catatan:
- Root harus menunjuk ke folder `public` Laravel, bukan root repository.
- PHP-CGI harus mendengarkan pada `127.0.0.1:9000`.
- Nginx menggunakan working directory `C:\nginx` agar proses berjalan konsisten.

## 4. Masalah Awal: Menjalankan Service dari GitHub Actions

Percobaan awal menjalankan proses long-running secara langsung dari job GitHub Actions tidak bertahan setelah job selesai.

Penyebab:
- GitHub Actions runner menjalankan proses sebagai bagian dari lifecycle job.
- Proses child yang dibuat oleh job tidak cocok dijadikan service permanen.
- Setelah job selesai, proses tersebut dapat ikut dihentikan.

Solusi yang dipilih:

**Windows Task Scheduler menjadi process supervisor untuk service DANUM.**

GitHub Actions hanya melakukan deployment dan meminta Task Scheduler menjalankan service.

## 5. GitHub Actions Self-Hosted Runner

Runner digunakan pada laptop Windows yang sama dengan DANUM.

Nama runner:

```text
 danum-laptop
```

Label yang digunakan:

```text
self-hosted
windows
danum
```

Job CD menggunakan:

```yaml
runs-on: [self-hosted, windows, danum]
```

Status runner yang sudah diverifikasi setelah konfigurasi:

```text
Idle
```

Artinya runner terdaftar dan siap menerima job.

## 6. Windows Task Scheduler

Empat task utama DANUM:

```text
DANUM - Nginx
DANUM - PHP-CGI
DANUM - Scheduler
DANUM - Queue Worker
```

Task dibuat melalui:

```text
install-danum-tasks.bat
```

atau PowerShell:

```text
scripts\install-danum-tasks.ps1
```

Installer menggunakan:

```text
AtLogOn
```

dengan interactive logon user Windows.

Konfigurasi ini berarti service DANUM otomatis diminta berjalan ketika user login ke Windows.

### Detail task

#### Nginx

```text
Execute:
C:\nginx\nginx.exe

WorkingDirectory:
C:\nginx
```

#### PHP-CGI

```text
Execute:
C:\Users\yudhistira\.config\herd\bin\php84\php-cgi.exe

Arguments:
-b 127.0.0.1:9000

WorkingDirectory:
C:\Users\yudhistira\Herd\danum
```

#### Scheduler

```text
Execute:
C:\Users\yudhistira\.config\herd\bin\php84\php.exe

Arguments:
artisan schedule:work

WorkingDirectory:
C:\Users\yudhistira\Herd\danum
```

#### Queue Worker

```text
Execute:
C:\Users\yudhistira\.config\herd\bin\php84\php.exe

Arguments:
artisan queue:work database --queue=default --tries=3 --timeout=900 -vvv

WorkingDirectory:
C:\Users\yudhistira\Herd\danum
```

Task menggunakan `ExecutionTimeLimit = 0`, sehingga tidak diberi batas waktu eksekusi oleh Task Scheduler.

## 7. Script Operasional

### Start

```text
start-danum.bat
```

Script menjalankan:

```text
scripts\start-danum.ps1
```

Start tidak lagi menjalankan executable secara langsung. Ia memanggil Task Scheduler untuk menjalankan empat task DANUM.

### Stop

```text
stop-danum.bat
```

Script menjalankan:

```text
scripts\stop-danum.ps1
```

Stop terlebih dahulu menghentikan task Task Scheduler agar proses tidak langsung hidup kembali, kemudian melakukan fallback cleanup terhadap proses DANUM yang dikenal.

Proses yang diperiksa/dihentikan secara selektif:
- Laravel Scheduler.
- Laravel Queue Worker.
- PHP-CGI yang listen di port `9000`.
- Nginx.

Stop tidak membunuh sembarang proses pada port tersebut; PHP-CGI diverifikasi berdasarkan nama proses.

### Health Check

```text
check-danum.bat
```

Health check memverifikasi empat komponen:

1. Nginx dan port `80`.
2. PHP-CGI pada port `9000`.
3. Laravel Scheduler.
4. Laravel Queue Worker.

Jika semua sehat, hasil akhirnya:

```text
STATUS: DANUM SIAP DIGUNAKAN
```

## 8. CD GitHub Actions

Workflow:

```text
.github/workflows/cd.yml
```

CD saat ini **manual**, menggunakan:

```yaml
on:
  workflow_dispatch:
```

Auto-deploy belum diaktifkan.

Concurrency:

```yaml
concurrency:
  group: danum-deploy
  cancel-in-progress: false
```

Tujuannya mencegah dua deployment DANUM berjalan bersamaan.

## 9. Urutan Deployment

Urutan CD saat ini:

```text
1. Check repository status
2. Stop DANUM services
3. git fetch origin master
4. git pull --ff-only origin master
5. Backup PostgreSQL
6. composer install
7. npm ci
8. npm run build
9. php artisan migrate --force --ansi
10. php artisan optimize
11. Start DANUM services
12. Health check
13. Deployment selesai
```

Repository yang memiliki perubahan lokal akan ditolak agar deployment tidak menimpa perubahan lokal.

## 10. Backup Database Sebelum Migration

Script:

```text
scripts\backup-danum-db.ps1
```

Backup menggunakan:

```text
C:\Program Files\PostgreSQL\18\bin\pg_dump.exe
```

Output disimpan ke:

```text
C:\Users\yudhistira\Herd\danum-backups
```

Format backup:

```text
Custom PostgreSQL dump (.dump)
```

Nama file:

```text
danum-YYYYMMDD-HHMMSS.dump
```

Backup dilakukan **setelah source code terbaru berhasil diambil tetapi sebelum dependency installation, build, dan terutama migration**.

Konfigurasi database dibaca dari `.env`:

```text
DB_CONNECTION
DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD
```

Password tidak diberikan sebagai argument command line. Script memasangnya sementara sebagai environment variable `PGPASSWORD`, kemudian menghapusnya setelah proses selesai.

### Validasi backup

Backup harus:

- berhasil dibuat;
- file ditemukan;
- ukuran file lebih dari 0;
- lolos pemeriksaan struktur menggunakan `pg_restore --list`.

Validasi ini tidak melakukan restore dan tidak mengubah database produksi.

Retention saat ini:

```text
10 backup terbaru
```

## 11. Mengapa Tidak Ada Automatic Migration Rollback

Deployment tidak menjalankan:

```text
php artisan migrate:rollback
```

secara otomatis ketika deployment gagal.

Alasannya:
- Migration dapat mengubah data existing.
- Sebagian migration merupakan repair/backfill/integrity migration.
- Rollback migration tidak selalu merupakan kebalikan sempurna dari perubahan data.
- Beberapa migration memang dirancang untuk menghentikan deployment ketika kondisi data tidak aman.

Strategi yang digunakan:

```text
Backup database
    ↓
Migration
    ↓
Jika gagal
    ↓
Service dipulihkan
    ↓
Backup tetap tersedia untuk restore manual
```

Ini lebih aman daripada memaksakan rollback migration otomatis.

## 12. Recovery Deployment

Jika deployment berhenti setelah service berhasil dihentikan, workflow akan mencoba:

```text
start-danum.ps1
        ↓
wait
        ↓
check-danum.ps1
```

Jika health check deployment setelah start gagal, recovery juga dijalankan.

Tujuan recovery adalah memastikan DANUM kembali hidup, bukan mengembalikan database secara otomatis.

## 13. Audit Migration — Prinsip Operasional

Hasil audit migration menunjukkan beberapa kategori risiko.

### Relatif aman

- Create table.
- Add nullable column.
- Index biasa.
- Struktur tambahan yang tidak memaksa data existing berubah.

### Perlu perhatian

- Foreign key.
- Unique constraint.
- Perubahan constraint.
- Perubahan struktur yang menyentuh data existing.

### Risiko tinggi

- Backfill data.
- Repair data.
- Delete/rebuild permission matrix.
- Perubahan existing records yang harus memenuhi constraint baru.

### Migration integrity guard

Migration:

```text
2026_08_29_062000_harden_custom_role_scope.php
```

dapat sengaja menghentikan deployment jika tenant custom role tidak dapat ditentukan secara unik.

Prinsipnya: **jangan menebak data tenant yang ambigu. Lebih baik migration gagal daripada menghasilkan data yang salah.**

## 14. Migration Khusus yang Pernah Diaudit

### Letter type versions

```text
2026_08_25_150000_harden_letter_type_versions.php
```

Menambah struktur versioning, foreign key, dan index.

### Backfill variables

```text
2026_08_25_120000_add_variables_to_letter_type_versions_table.php
```

Melakukan backfill dari data `letter_types.variables` ke `letter_type_versions`.

### RBAC repair

Migration yang pernah diidentifikasi sebagai repair/integrity migration meliputi:

```text
seed_default_rbac_role_permissions
repair_default_rbac_role_permissions
repair_default_rbac_system_roles
repair_tenant_admin_rbac_permissions
```

Salah satu repair RBAC menghapus permission mapping system role lalu membangunnya kembali. Ini disengaja, tetapi memiliki dampak operasional tinggi sehingga harus diperlakukan sebagai migration khusus.

### Letter classification

```text
2026_09_07_000003_add_letter_classification_to_letter_types.php
```

Migration ini:

1. memastikan classification `000 / Umum` tersedia;
2. mengisi classification untuk `letter_types` lama;
3. kemudian menjadikan classification wajib.

Karena ada backfill dan perubahan constraint, migration ini tidak boleh dianggap sekadar perubahan struktur.

## 15. Error dan Solusi yang Ditemukan

### Error 1 — Service mati setelah GitHub Actions selesai

**Gejala:**

Deployment job selesai, tetapi Nginx/PHP-CGI/Scheduler/Queue Worker tidak tetap hidup.

**Penyebab:**

Proses long-running yang dibuat langsung oleh GitHub Actions tidak cocok menjadi service permanen dan mengikuti lifecycle runner job.

**Solusi:**

Gunakan Windows Task Scheduler sebagai supervisor service. GitHub Actions cukup melakukan stop/start melalui task tersebut.

---

### Error 2 — Nginx Task tidak konsisten karena working directory

**Gejala:**

Task Nginx sudah terdaftar tetapi proses tidak berjalan sebagaimana ketika Nginx dijalankan manual.

**Penyebab:**

Nginx memerlukan working directory yang konsisten dengan instalasinya.

**Solusi:**

Task Nginx menggunakan:

```text
Execute: C:\nginx\nginx.exe
WorkingDirectory: C:\nginx
```

---

### Error 3 — Task Nginx memiliki argument kosong

**Gejala:**

Action Task Scheduler menghasilkan konfigurasi argument yang tidak diperlukan untuk Nginx.

**Solusi:**

Installer membedakan executable tanpa argument dari executable yang memang membutuhkan argument. Nginx dibuat tanpa `-Argument` kosong.

---

### Error 4 — Service otomatis hidup kembali setelah stop

**Gejala:**

Proses yang dihentikan dapat muncul kembali karena Task Scheduler masih memiliki task aktif.

**Solusi:**

`stop-danum.ps1` menghentikan Scheduled Task terlebih dahulu, baru melakukan cleanup process.

---

### Error 5 — Backup dipanggil sebelum script backup tersedia di checkout

**Gejala:**

CD memanggil `backup-danum-db.ps1` sebelum source code terbaru diambil.

**Penyebab:**

Script backup baru ada pada commit terbaru.

**Solusi:**

Urutan diperbaiki menjadi:

```text
git fetch
↓
git pull
↓
backup-danum-db.ps1
```

Dengan demikian script backup berasal dari source code yang baru saja dideploy.

---

### Error 6 — Migration gagal tidak boleh otomatis rollback

**Gejala:**

Ada kekhawatiran deployment gagal setelah migration sebagian berjalan.

**Solusi yang dipilih:**

Tidak menggunakan automatic `migrate:rollback`. Database selalu dibackup sebelum migration sehingga restore dapat dilakukan secara manual jika benar-benar diperlukan.

---

### Error 7 — Recovery tidak boleh hanya menghidupkan service

**Gejala:**

Service bisa saja berhasil diminta start tetapi belum benar-benar sehat.

**Solusi:**

Recovery selalu diikuti health check empat komponen.

## 16. Prosedur Harian

### Menjalankan DANUM

```bat
start-danum.bat
```

Kemudian:

```bat
check-danum.bat
```

Target:

```text
4/4 service OK
STATUS: DANUM SIAP DIGUNAKAN
```

### Menghentikan DANUM

```bat
stop-danum.bat
```

### Deploy manual

Pastikan repository laptop bersih, kemudian jalankan workflow:

```text
GitHub → Actions → CD → Run workflow → master
```

Setelah green, verifikasi:

```bat
check-danum.bat
```

## 17. Verifikasi Backup Tanpa Restore

Untuk melihat isi metadata backup tanpa mengubah database:

```powershell
& 'C:\Program Files\PostgreSQL\18\bin\pg_restore.exe' --list 'C:\Users\yudhistira\Herd\danum-backups\danum-YYYYMMDD-HHMMSS.dump'
```

Perintah tersebut hanya membaca daftar objek dari dump.

## 18. Catatan Keamanan Backup

Backup saat ini disimpan pada laptop yang sama:

```text
C:\Users\yudhistira\Herd\danum-backups
```

Ini melindungi dari kegagalan deployment atau migration, tetapi **belum melindungi dari kerusakan disk/laptop**.

Pengembangan berikutnya yang disarankan:

```text
PostgreSQL backup lokal
        ↓
backup kedua
        ↓
NAS / server lain / storage eksternal
```

Lokasi dan mekanisme remote backup belum ditetapkan dalam konfigurasi saat ini.

## 19. Prinsip Operasional DANUM

1. `master` adalah source deployment.
2. Deployment tetap manual sampai benar-benar stabil.
3. Repository lokal harus bersih sebelum CD.
4. Service dihentikan sebelum deployment.
5. Database dibackup sebelum migration.
6. Backup harus lolos validasi.
7. Migration tidak di-rollback otomatis.
8. Service dijalankan melalui Windows Task Scheduler.
9. Setiap deployment harus diikuti health check.
10. Jika deployment gagal, prioritas pertama adalah memulihkan service.
11. Database restore dilakukan manual dan hanya jika diperlukan.
12. Jangan menebak atau memperbaiki data ambigu secara otomatis pada migration integrity guard.

## 20. Status Saat Dokumen Ini Dibuat

Status operasional terakhir yang sudah diverifikasi:

```text
Nginx             OK
PHP-CGI           OK
Laravel Scheduler OK
Laravel Queue     OK
GitHub Runner     Idle / siap
CD                Manual
Database backup   Aktif sebelum migration
Recovery          Aktif
```

Dokumen ini dimaksudkan sebagai referensi operasional dan harus diperbarui jika path, versi runtime, hostname, workflow, atau mekanisme deployment berubah.
