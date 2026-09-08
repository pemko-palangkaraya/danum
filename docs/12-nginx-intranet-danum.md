# Nginx & Intranet DANUM

> Buku saku konfigurasi Nginx, PHP, firewall, hostname, ZeroTier, dan asset Vite untuk menjalankan DANUM melalui jaringan internal.
>
> **Status:** Infrastruktur dasar sudah diuji. Konfigurasi aplikasi DANUM sendiri masih dalam pengembangan.

## 1. Arsitektur

```text
Client PC / HP
      |
      | LAN / ZeroTier
      v
Server DANUM
  +-- Nginx :80
  +-- PHP FastCGI
  +-- Laravel
  `-- PostgreSQL
```

Nginx menerima request HTTP, meneruskan PHP melalui FastCGI, lalu Laravel menangani aplikasi. PostgreSQL berada di sisi server.

## 2. Lokasi server dan project

Nginx dipasang di:

```text
C:\nginx
```

Konfigurasi utama:

```text
C:\nginx\conf\nginx.conf
```

Project DANUM berada di:

```text
C:\Users\yudhistira\Herd\danum
```

Document root Nginx harus menunjuk ke folder `public`:

```text
C:\Users\yudhistira\Herd\danum\public
```

Jangan menjadikan root project Laravel sebagai document root.

## 3. PHP wajib lewat FastCGI

PHP tidak boleh disajikan Nginx sebagai file statis. Jika blok PHP FastCGI tidak aktif, browser dapat menampilkan source `index.php`.

Alur yang benar:

```text
Browser
   |
   v
Nginx
   |
   v
PHP FastCGI
   |
   v
Laravel
```

Pastikan konfigurasi server memiliki handler `.php` yang meneruskan request ke PHP-CGI/FastCGI dan `SCRIPT_FILENAME` menunjuk ke file yang benar.

## 4. Contoh struktur server Nginx

Bagian inti konfigurasi harus mengikuti pola berikut:

```nginx
server {
    listen 80;
    server_name danum.internal;

    root C:\Users\yudhistira\Herd\danum\public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        # fastcgi_pass diarahkan ke PHP FastCGI yang digunakan server.
        # Contoh parameter FastCGI disesuaikan dengan instalasi PHP lokal.
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

> Jangan menyalin nilai `fastcgi_pass` secara membabi buta. Nilainya harus sesuai dengan instalasi PHP pada mesin server.

## 5. Validasi konfigurasi Nginx

Dari PowerShell:

```powershell
cd C:\nginx
.\nginx.exe -t
```

Target:

```text
syntax is ok
test is successful
```

Jika `nginx -t` gagal, perbaiki konfigurasi terlebih dahulu sebelum reload/restart.

## 6. Menjalankan / reload Nginx

Reload setelah konfigurasi valid:

```powershell
cd C:\nginx
.\nginx.exe -s reload
```

Jika Nginx belum berjalan:

```powershell
.\nginx.exe
```

Menghentikan Nginx:

```powershell
.\nginx.exe -s stop
```

## 7. Test dari server

Uji dari komputer server:

```powershell
curl.exe http://127.0.0.1
```

atau:

```powershell
curl.exe http://localhost
```

Jika Laravel tampil normal, jalur dasar berikut sudah berfungsi:

```text
Nginx -> PHP -> Laravel
```

## 8. Windows Firewall

Agar client dapat mengakses Nginx, TCP port `80` harus diizinkan.

PowerShell harus **Run as Administrator**.

```powershell
New-NetFirewallRule `
    -DisplayName "Danum Nginx HTTP" `
    -Direction Inbound `
    -Protocol TCP `
    -LocalPort 80 `
    -Action Allow `
    -Profile Private
```

Catatan penting: saat menjalankan command, paste hanya command-nya. Jangan ikut menyalin prompt PowerShell atau output/error sebelumnya.

## 9. Akses melalui LAN

Jika server mempunyai IP LAN, misalnya:

```text
192.168.1.10
```

client pada jaringan yang sama dapat menguji:

```text
http://192.168.1.10
```

Jika server bisa membuka DANUM tetapi client tidak bisa, periksa:

```text
IP server
Firewall
Network profile
TCP port 80
Nginx
```

## 10. `localhost` bukan alamat server

`localhost` selalu merujuk ke perangkat yang sedang membuka alamat tersebut.

```text
Server: localhost -> server
HP:     localhost -> HP
Laptop: localhost -> laptop
```

Karena itu `http://localhost` tidak boleh dianggap sebagai hostname bersama untuk akses intranet.

## 11. Hostname internal

Target penggunaan yang lebih nyaman adalah hostname internal, misalnya:

```text
http://danum.internal
```

Alur:

```text
danum.internal
      |
      v
DNS internal
      |
      v
IP server
      |
      v
Nginx
      |
      v
Laravel
```

Nginx kemudian menggunakan:

```nginx
server_name danum.internal;
```

dan Laravel dapat menggunakan:

```env
APP_URL=http://danum.internal
```

Setelah mengubah `.env`:

```powershell
php artisan optimize:clear
```

## 12. Hosts file untuk testing

Jika belum tersedia DNS internal, hostname dapat diuji melalui hosts file Windows:

```text
C:\Windows\System32\drivers\etc\hosts
```

Contoh:

```text
192.168.1.10 danum.internal
```

Kelemahannya: entry harus dibuat pada setiap client. Jadi hosts file cocok untuk testing atau jaringan kecil, bukan solusi DNS terpusat.

## 13. ZeroTier

ZeroTier dapat digunakan sebagai jaringan virtual untuk menghubungkan client dengan server DANUM, termasuk ketika perangkat tidak berada pada LAN fisik yang sama.

Konsep:

```text
Client
  |
  | ZeroTier
  v
Server DANUM
```

Di server, setelah network dibuat dan Network ID tersedia:

```powershell
zerotier-cli join NETWORK_ID
```

Network kemudian perlu di-authorize melalui ZeroTier Central.

Periksa koneksi:

```powershell
zerotier-cli listnetworks
```

Setelah server memperoleh IP ZeroTier, uji dari client:

```powershell
ping IP_ZEROTIER_SERVER
```

Kemudian uji TCP port 80:

```powershell
Test-NetConnection IP_ZEROTIER_SERVER -Port 80
```

ZeroTier sendiri tidak otomatis berarti hostname `danum.internal` tersedia. Untuk hostname tetap dibutuhkan mekanisme DNS/hostname internal.

## 14. Vite dan asset CSS/JS

Saat development aktif:

```powershell
npm run dev
```

Laravel dapat mengarah ke Vite development server. Ini dapat menyebabkan masalah ketika aplikasi dibuka dari perangkat lain karena `localhost` pada browser client menunjuk ke client tersebut.

Untuk deployment/intranet:

```powershell
npm run build
```

Asset hasil build berada di:

```text
public/build
```

Alur deployment yang diharapkan:

```text
Browser
   |
   v
Nginx
   |
   v
public/build
```

Jangan mengandalkan `npm run dev` sebagai server asset untuk deployment intranet.

## 15. Checklist troubleshooting

Jika DANUM tidak dapat dibuka dari client, cek berurutan:

```text
[ ] Nginx berjalan
[ ] nginx -t berhasil
[ ] root Nginx menunjuk ke /public
[ ] PHP FastCGI aktif
[ ] Laravel dapat dibuka dari localhost/server
[ ] Firewall TCP 80 terbuka
[ ] Client dapat mencapai IP server
[ ] Client dapat konek ke port 80
[ ] APP_URL sesuai hostname yang digunakan
[ ] npm run build sudah dijalankan
```

### Browser menampilkan source PHP

Kemungkinan utama: PHP FastCGI belum benar/aktif.

### Server bisa dibuka, HP tidak bisa

Periksa firewall, IP server, network profile, dan port 80.

### HTML tampil tetapi CSS/JS bermasalah

Periksa hasil `npm run build`, folder `public/build`, dan apakah `public/hot`/Vite development server masih digunakan.

### `localhost` dari HP tidak membuka DANUM

Normal. Gunakan IP server atau hostname internal.

## 16. Target akhir

Untuk tahap pengujian LAN:

```text
http://192.168.1.10
```

Target hostname internal:

```text
http://danum.internal
```

Jika menggunakan ZeroTier:

```text
Client
  |
  v
ZeroTier
  |
  v
DNS internal
  |
  v
danum.internal
  |
  v
Nginx :80
  |
  v
PHP
  |
  v
Laravel
```

## 17. Catatan status

Dokumen ini hanya mencatat konfigurasi **Nginx + akses intranet DANUM** yang dibahas dan diuji dalam sesi ini.

Pengembangan fitur DANUM sendiri belum dianggap selesai dan akan didokumentasikan terpisah.
