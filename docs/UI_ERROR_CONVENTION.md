# DANUM UI Error Convention

## Rule utama

> **Inline = “Apa yang salah dengan input saya?”**  
> **Toast = “Apa yang terjadi dengan aksi saya?”**

### Inline validation

Gunakan inline error di dekat field ketika masalah berasal dari nilai input pengguna, misalnya:

- field wajib kosong;
- format email/tanggal tidak valid;
- panjang/nilai input tidak sesuai;
- kode sudah digunakan;
- kombinasi field tidak valid.

Validation error **tidak boleh dipindahkan ke toast**.

```blade
@error('email')
    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
@enderror
```

### Toast

Gunakan toast ketika yang ingin dijelaskan adalah hasil sebuah aksi atau kondisi aplikasi, misalnya:

- berhasil menyimpan/memperbarui/menghapus;
- aksi ditolak karena permission;
- business rule mencegah aksi;
- upload/generate/send gagal;
- service/API/database/infrastructure gagal.

Pesan harus human-readable dan tidak membocorkan raw exception.

### Authorization

Authorization failure diperlakukan sebagai **toast**, bukan inline validation.

Contoh:

> Anda tidak memiliki izin untuk melakukan aksi ini.

Tetap lakukan authorization di server/policy. Toast hanya menentukan cara error disajikan ke pengguna.

### Livewire form

Urutan yang disarankan:

1. authorize/cek permission;
2. validate input;
3. jalankan service/action;
4. jika sukses, tutup/reset form dan kirim success toast;
5. jika validation gagal, biarkan error bag dirender inline;
6. jika business/system/action gagal, kirim error toast.

Jangan mengambil validation error pertama lalu menampilkannya sebagai toast.
