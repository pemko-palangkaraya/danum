# DANUM — Outgoing Letter UX

**Status:** Implemented  
**Scope:** Form Surat Keluar

## 1. Auto-focus validation

Saat form Surat Keluar disimpan dan terdapat validation error, aplikasi otomatis mencari field error pertama.

Perilaku:

```text
Klik Simpan Draft
      ↓
Validasi backend
      ↓
Ada error?
   ┌──┴──┐
  Tidak  Ya
   ↓      ↓
 Simpan  Focus field error pertama
          ↓
       Scroll halus ke field
```

User tidak perlu mencari atau melakukan scroll manual ke field yang belum diisi.

Fokus hanya dijalankan setelah submit, sehingga tidak mengganggu pengguna ketika sedang mengetik.

## 2. NIK citizen autocomplete

Input NIK pada form Surat Keluar menyediakan suggestion warga berdasarkan prefix NIK.

Perilaku:

- suggestion mulai muncul setelah minimal 3 digit;
- maksimal 8 warga ditampilkan;
- hasil dibatasi pada tenant aktif pengguna;
- suggestion menampilkan nama lengkap dan NIK;
- memilih suggestion mengisi NIK dan menjalankan mekanisme pengisian data warga yang sudah ada;
- data warga lain seperti nama, TTL, alamat, dan variabel sumber warga tetap diisi melalui resolver yang sudah digunakan DANUM;
- pencarian tidak mengubah aturan validasi backend.

Contoh:

```text
Ketik: 627

┌──────────────────────────────┐
│ Nama Warga A                 │
│ NIK: 6271xxxxxxxxxxxx        │
├──────────────────────────────┤
│ Nama Warga B                 │
│ NIK: 6271xxxxxxxxxxxx        │
└──────────────────────────────┘
```

Pengguna cukup memilih nama yang sesuai.

## 3. Tenant isolation

Autocomplete tidak boleh mengambil warga lintas tenant.

Query suggestion selalu menggunakan `tenant_id` pengguna dan tidak membuka pencarian global.

## 4. Integrasi dengan pengisian otomatis

Autocomplete hanya menjadi bantuan pemilihan. Setelah suggestion dipilih, mekanisme `updatedVariableValues()` yang sudah ada tetap menjadi sumber pengisian data warga.

Dengan demikian tidak dibuat jalur kedua untuk mapping data warga.

## 5. Status implementasi

Fitur yang sudah berhasil diterapkan:

- auto-focus field validation error pertama;
- smooth scroll ke field error di dalam modal;
- NIK autocomplete berbasis prefix;
- pilihan nama warga;
- pengisian data warga otomatis setelah pilihan;
- pembatasan tenant pada hasil suggestion.

## 6. Catatan pengujian

Pengujian browser perlu dilakukan setelah asset aplikasi dibangun ulang jika deployment menggunakan Vite build.

```powershell
npm run build
```

Setelah itu lakukan hard refresh browser dan uji:

1. buka Buat Surat Keluar;
2. ketik minimal 3 digit NIK;
3. pastikan suggestion muncul;
4. pilih warga;
5. pastikan NIK dan data warga terisi;
6. kosongkan field wajib lain dan klik Simpan Draft;
7. pastikan fokus berpindah ke error pertama.
