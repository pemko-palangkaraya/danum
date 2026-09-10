# Backup Database & Restore DANUM

> Buku saku operasional untuk backup dan restore database PostgreSQL DANUM pada server Windows.
>
> **Status:** Backup otomatis dan prosedur restore database sudah dibuat dan diuji pada server development.
>
> **Catatan:** Dokumentasi ini hanya membahas **database**. Backup file dokumen/template pada `storage` belum termasuk dan akan dibahas terpisah.

---

## 1. Tujuan

DANUM menggunakan PostgreSQL sebagai database utama. Agar data dapat dipulihkan ketika terjadi kerusakan atau kesalahan, database dibackup secara berkala ke file `.dump`.

Target yang dibuat:

```text
Setiap hari pukul 23:00
        ↓
Windows Task Scheduler
        ↓
PowerShell
        ↓
pg_dump PostgreSQL
        ↓
C:\Users\yudhistira\Herd\danum-backups
```

Backup menggunakan format PostgreSQL **custom dump**, sehingga restore dilakukan menggunakan `pg_restore`.

---

## 2. Batasan backup saat ini

Yang dibackup:

```text
PostgreSQL database DANUM
```

Yang **belum** dibackup oleh mekanisme ini:

```text
storage/app/...
template files
generated documents
signed PDF
eksport file
```

GitHub juga bukan pengganti backup database operasional. Repository menyimpan source code, sedangkan database menyimpan data operasional.

Untuk saat ini backup disimpan pada server yang sama. Backup ke server/disk berbeda atau offsite merupakan peningkatan tahap berikutnya.

---

## 3. Konfigurasi database

Script membaca konfigurasi database dari file:

```text
C:\Users\yudhistira\Herd\danum\.env
```

Parameter yang digunakan:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=danum
DB_USERNAME=yudhistira
```

`DB_PASSWORD` juga dibaca oleh script, tetapi **jangan pernah ditulis ke dokumentasi, commit, screenshot, atau chat**.

---

## 4. File yang digunakan

### 4.1 Script backup

```text
scripts/backup-danum-db.ps1
```

Tugas script:

1. Membaca konfigurasi `.env`.
2. Memastikan PostgreSQL digunakan.
3. Menjalankan `pg_dump`.
4. Membuat file dengan nama timestamp.
5. Memastikan file backup ada dan tidak kosong.
6. Menjalankan `pg_restore --list` untuk memeriksa struktur dump.
7. Mempertahankan maksimal 10 backup terbaru.

File backup berbentuk:

```text
danum-YYYYMMDD-HHMMSS.dump
```

Contoh:

```text
danum-20260910-090454.dump
```

---

### 4.2 Script registrasi Task Scheduler

```text
scripts/register-danum-db-backup-task.ps1
```

Script ini membuat Windows Scheduled Task dengan konfigurasi:

```text
Task name : DANUM - Daily Database Backup
Schedule  : Every day at 23:00
Run as    : SYSTEM
```

Task menjalankan script backup menggunakan PowerShell dengan `ExecutionPolicy Bypass` hanya pada proses task tersebut.

---

### 4.3 Script restore

```text
scripts/restore-danum-db.ps1
```

Script restore menyediakan dua mode:

```text
1. Restore ke database baru
2. Restore ke database DANUM aktif
```

Mode 1 adalah mode yang direkomendasikan untuk pengujian.

Mode 2 bersifat destruktif dan memiliki pengaman tambahan.

---

## 5. Instalasi backup otomatis

### 5.1 Update repository di server

Dari PowerShell:

```powershell
cd C:\Users\yudhistira\Herd\danum
git pull origin master
```

### 5.2 Jalankan registrasi task

PowerShell dapat memblokir file `.ps1` karena Execution Policy. Jangan perlu mengubah policy Windows secara permanen. Jalankan script dengan bypass untuk proses ini saja:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\register-danum-db-backup-task.ps1
```

Jika berhasil, akan muncul:

```text
[OK] Task       : DANUM - Daily Database Backup
[OK] Jadwal     : Setiap hari pukul 23:00
[OK] Run as     : SYSTEM
```

---

## 6. Memeriksa Task Scheduler

Buka Windows:

```text
Task Scheduler
    ↓
Task Scheduler Library
```

Cari:

```text
DANUM - Daily Database Backup
```

Periksa:

### Triggers

Harus menunjukkan:

```text
At 11:00 PM every day
```

atau ekuivalen:

```text
Setiap hari pukul 23:00
```

### Actions

Harus menjalankan:

```text
powershell.exe
```

dengan script:

```text
C:\Users\yudhistira\Herd\danum\scripts\backup-danum-db.ps1
```

### Status

Setelah task selesai dijalankan, hasil sukses biasanya terlihat sebagai:

```text
Last Run Result: 0x0
```

`0x0` berarti task selesai tanpa error dari Task Scheduler.

> Task Scheduler kadang belum memperbarui tampilan setelah task dijalankan. Gunakan **Refresh** sebelum menyimpulkan hasil dari kolom `Last Run Result`.

---

## 7. Menguji backup secara manual

Tidak perlu menunggu pukul 23:00 untuk menguji.

Dari folder project:

```powershell
cd C:\Users\yudhistira\Herd\danum
```

Jalankan:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\backup-danum-db.ps1
```

Jika PowerShell berada di folder `scripts`, gunakan:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\backup-danum-db.ps1
```

Jangan menggunakan:

```powershell
.\scripts\backup-danum-db.ps1
```

ketika current directory sudah berada di `scripts`, karena PowerShell akan mencari `scripts\scripts\...`.

---

## 8. Lokasi hasil backup

Backup disimpan di:

```text
C:\Users\yudhistira\Herd\danum-backups
```

Contoh:

```text
C:\Users\yudhistira\Herd\danum-backups\
    danum-20260910-090454.dump
    danum-20260910-090446.dump
    danum-20260910-090403.dump
```

Script mempertahankan maksimal 10 backup reguler terbaru.

---

## 9. Pemeriksaan manual isi folder backup

PowerShell:

```powershell
Get-ChildItem C:\Users\yudhistira\Herd\danum-backups -Filter "danum-*.dump" |
    Sort-Object LastWriteTime -Descending
```

Untuk melihat ukuran file:

```powershell
Get-ChildItem C:\Users\yudhistira\Herd\danum-backups -Filter "danum-*.dump" |
    Select-Object Name, Length, LastWriteTime
```

File backup yang berhasil harus memiliki ukuran lebih dari 0 byte.

---

## 10. Konsep verifikasi backup

Membuat file `.dump` saja belum cukup. Script backup melakukan pemeriksaan tambahan:

```text
pg_dump
   ↓
file .dump dibuat
   ↓
cek file ada
   ↓
cek ukuran > 0
   ↓
pg_restore --list
   ↓
backup dianggap valid secara struktur
```

Pemeriksaan ini **tidak melakukan restore** dan tidak mengubah database sumber.

---

# RESTORE DATABASE

## 11. Prinsip restore

Restore berarti mengambil isi dari file `.dump` dan memasukkannya kembali ke PostgreSQL.

Ada dua skenario:

```text
Mode 1
backup → database baru
```

dan:

```text
Mode 2
backup → database DANUM aktif
```

**Selalu gunakan Mode 1 untuk pengujian.**

---

## 12. Menjalankan script restore

Dari project:

```powershell
cd C:\Users\yudhistira\Herd\danum
```

Jalankan:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\restore-danum-db.ps1
```

Jika current directory sudah berada di `scripts`:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\restore-danum-db.ps1
```

---

## 13. Memilih backup

Script menampilkan daftar backup yang tersedia, misalnya:

```text
Backup tersedia:

[1] danum-20260910-090454.dump - 0.26 MB
[2] danum-20260910-090446.dump - 0.26 MB
[3] danum-20260910-090403.dump - 0.26 MB
```

Pilih nomor backup:

```text
Pilih nomor backup untuk restore: 1
```

---

## 14. Mode 1 — Restore ke database baru

Pilih:

```text
Pilih mode restore: 1
```

Kemudian masukkan nama database test, misalnya:

```text
danum-test
```

Alurnya:

```text
backup .dump
      ↓
verifikasi dump
      ↓
buat database danum-test
      ↓
pg_restore
      ↓
danum-test
```

Database aktif:

```text
danum
```

**tidak disentuh**.

Contoh hasil sukses:

```text
[VERIFY] Memeriksa integritas backup...
[CREATE] Membuat database: danum-test
[RESTORE] Memulihkan backup ke database test...

[OK] Restore test berhasil ke database: danum-test
[OK] Database DANUM aktif tidak disentuh.
```

---

## 15. Menghapus database test setelah selesai

Jika database test sudah tidak diperlukan:

```powershell
& "C:\Program Files\PostgreSQL\18\bin\dropdb.exe" -U yudhistira -h 127.0.0.1 -p 5432 danum-test
```

Jika `dropdb` sudah tersedia di PATH:

```powershell
dropdb -U yudhistira -h 127.0.0.1 -p 5432 danum-test
```

Untuk memeriksa daftar database:

```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U yudhistira -h 127.0.0.1 -p 5432 -l
```

Pastikan:

```text
danum
```

masih ada dan:

```text
danum-test
```

sudah tidak ada.

**Jangan menghapus database `danum`.**

---

# RESTORE KE DATABASE AKTIF

## 16. Mode 2 — Restore ke database DANUM aktif

Mode ini digunakan hanya ketika memang diperlukan untuk mengembalikan database aktif ke kondisi backup tertentu.

Pilih:

```text
Pilih mode restore: 2
```

Script akan memberi peringatan:

```text
PERINGATAN: database DANUM AKTIF akan diganti dari backup.
Data yang dibuat setelah waktu backup dapat hilang.
```

Untuk melanjutkan harus mengetik persis:

```text
RESTORE DANUM
```

---

## 17. Safety backup sebelum restore aktif

Sebelum mengganti database aktif, script membuat backup pengaman:

```text
danum-before-restore-YYYYMMDD-HHMMSS.dump
```

Contoh:

```text
danum-before-restore-20260910-092105.dump
```

Alurnya:

```text
Database DANUM aktif
        ↓
backup pengaman
        ↓
restore backup yang dipilih
```

Jika backup pengaman gagal, restore **dibatalkan**.

Ini adalah pengaman penting agar kita tidak menghancurkan database aktif ketika backup sebelum restore tidak berhasil.

---

## 18. Apa yang terjadi saat restore aktif

Setelah konfirmasi dan safety backup berhasil:

```text
pg_restore
    ↓
--clean
    ↓
--if-exists
    ↓
restore ke database danum
```

Data yang dibuat setelah waktu backup dapat hilang.

Karena itu Mode 2 bukan prosedur rutin. Gunakan hanya ketika benar-benar diperlukan.

---

## 19. Insiden yang pernah ditemukan saat implementasi

### 19.1 PowerShell memblokir `.ps1`

Error:

```text
cannot be loaded because running scripts is disabled on this system
```

Solusi yang digunakan:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\restore-danum-db.ps1
```

Tidak perlu mengubah Execution Policy Windows secara permanen.

---

### 19.2 Salah path karena sudah berada di folder `scripts`

Jika prompt menunjukkan:

```text
PS C:\Users\yudhistira\Herd\danum\scripts>
```

maka jangan menjalankan:

```powershell
.\scripts\restore-danum-db.ps1
```

Gunakan:

```powershell
.\restore-danum-db.ps1
```

atau:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\restore-danum-db.ps1
```

---

### 19.3 Username PostgreSQL berbeda dengan nama database

Konfigurasi aktual DANUM menggunakan:

```text
DB_DATABASE=danum
DB_USERNAME=yudhistira
```

Artinya PostgreSQL harus diarahkan eksplisit ke database `danum` saat melakukan backup.

Jika tidak, PostgreSQL dapat mencoba menggunakan nama username sebagai nama database dan menghasilkan error seperti:

```text
FATAL: database "yudhistira" does not exist
```

Script restore sudah diperbaiki agar backup pengaman menggunakan database yang dibaca dari `DB_DATABASE` secara eksplisit.

---

## 20. Pengujian yang sudah dilakukan

Siklus berikut sudah diuji:

```text
1. Backup database DANUM
        ↓
2. File .dump berhasil dibuat
        ↓
3. Integritas dump diperiksa
        ↓
4. Restore ke database test
        ↓
5. Database test berhasil dibuat
        ↓
6. Restore berhasil
        ↓
7. Database DANUM aktif tidak tersentuh
```

Mode restore aktif juga diuji dengan safety backup sebelum restore.

---

## 21. Prosedur cepat: backup manual

```powershell
cd C:\Users\yudhistira\Herd\danum
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\backup-danum-db.ps1
```

Hasil berada di:

```text
C:\Users\yudhistira\Herd\danum-backups
```

---

## 22. Prosedur cepat: restore test

```powershell
cd C:\Users\yudhistira\Herd\danum
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\restore-danum-db.ps1
```

Kemudian:

```text
Pilih backup
→ pilih mode 1
→ masukkan nama database test, misalnya danum-test
```

---

## 23. Prosedur cepat: hapus database test

```powershell
& "C:\Program Files\PostgreSQL\18\bin\dropdb.exe" -U yudhistira -h 127.0.0.1 -p 5432 danum-test
```

---

## 24. Prosedur darurat: restore database aktif

Gunakan hanya jika benar-benar diperlukan.

```powershell
cd C:\Users\yudhistira\Herd\danum
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\restore-danum-db.ps1
```

Kemudian:

```text
Pilih backup
→ pilih mode 2
→ baca peringatan
→ ketik RESTORE DANUM
→ pastikan safety backup berhasil
→ restore berjalan
```

Jangan melewati safety backup.

---

## 25. Checklist operasional

### Backup otomatis

```text
[ ] Task Scheduler aktif
[ ] Nama task: DANUM - Daily Database Backup
[ ] Jadwal: setiap hari 23:00
[ ] Run as: SYSTEM
[ ] Last Run Result: 0x0 setelah eksekusi berhasil
[ ] File .dump muncul di danum-backups
```

### Backup file

```text
[ ] File memiliki ukuran > 0 byte
[ ] Nama file memiliki timestamp
[ ] pg_restore --list berhasil
```

### Restore test

```text
[ ] Pilih backup yang benar
[ ] Pilih Mode 1
[ ] Gunakan nama database test berbeda dari danum
[ ] Restore berhasil
[ ] Database aktif tidak berubah
```

### Restore aktif

```text
[ ] Pastikan backup yang dipilih benar
[ ] Pahami data setelah waktu backup dapat hilang
[ ] Safety backup berhasil
[ ] Konfirmasi RESTORE DANUM diketik dengan benar
[ ] Setelah restore lakukan pengecekan aplikasi
```

---

## 26. Target pengembangan berikutnya

Mekanisme saat ini sengaja dibuat sederhana dan aman untuk server development/intranet.

Peningkatan yang dapat dilakukan kemudian:

1. Backup ke media/server berbeda.
2. Backup offsite.
3. Retensi lebih panjang dari 10 backup.
4. Monitoring kegagalan backup.
5. Verifikasi restore terjadwal.
6. Manifest/checksum backup.
7. SOP disaster recovery yang lebih lengkap.
8. Backup storage dokumen dan template.

Untuk saat ini, **backup database dan restore database merupakan dua hal yang sudah tersedia dan telah diuji secara manual.**
