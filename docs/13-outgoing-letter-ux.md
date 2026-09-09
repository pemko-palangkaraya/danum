# DANUM — Outgoing Letter UX

**Status:** Implemented  
**Scope:** Form Surat Keluar

## 1. Auto-focus validation

Saat form Surat Keluar disimpan dan terdapat validation error, aplikasi otomatis mencari field error pertama.

User tidak perlu mencari atau melakukan scroll manual ke field yang belum diisi. Fokus hanya dijalankan setelah submit.

## 2. NIK citizen autocomplete

Input NIK pada form Surat Keluar menyediakan suggestion warga berdasarkan prefix NIK.

Perilaku:

- suggestion mulai muncul setelah minimal 3 digit;
- maksimal 8 warga ditampilkan;
- hasil dibatasi pada tenant aktif pengguna;
- suggestion menampilkan nama lengkap dan NIK;
- memilih suggestion menjalankan mekanisme pengisian data warga yang sudah ada;
- mapping data tetap menggunakan resolver DANUM yang sudah ada;
- pencarian tidak mengubah aturan validasi backend.

## 3. Tenant isolation

Autocomplete selalu menggunakan `tenant_id` pengguna dan tidak membuka pencarian warga lintas tenant.

## 4. Status implementasi

Fitur yang sudah diterapkan:

- auto-focus field validation error pertama;
- smooth scroll ke field error di dalam modal;
- NIK autocomplete berbasis prefix;
- pilihan nama warga;
- pengisian data warga otomatis setelah pilihan;
- pembatasan tenant pada hasil suggestion.

## 5. Pengujian browser

Setelah deployment menggunakan Vite build:

```powershell
npm run build
```

Kemudian hard refresh dan uji ketik minimal 3 digit NIK, pilih warga, pastikan data terisi otomatis, lalu uji kembali validasi field wajib.
