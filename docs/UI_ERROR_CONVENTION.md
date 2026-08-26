# DANUM UI Convention

## 1. Error presentation

### Rule utama

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

## 2. Tombol `...` / Action Menu

Semua tombol `...` yang membuka menu aksi pada list/table **wajib mengikuti pola floating action menu yang sama**. Tujuannya agar menu tidak pernah terpotong oleh `overflow-hidden`, card, table wrapper, modal container, atau stacking context parent.

### Rule wajib

1. **Trigger konsisten**
   - gunakan tombol icon `...` dengan ukuran dan hover state yang sama;
   - `aria-haspopup="true"`;
   - `aria-expanded` mengikuti state menu;
   - `aria-label` harus menjelaskan konteks, misalnya `User actions` atau `Tenant actions`.

2. **Menu harus keluar dari container tabel/card**
   - gunakan Alpine `x-teleport="body"`;
   - jangan menaruh dropdown langsung sebagai child dari `<tr>`, `<td>`, wrapper `overflow-hidden`, atau container dengan clipping.

3. **Menu menggunakan viewport positioning**
   - gunakan `position: fixed`;
   - posisi dihitung dari `getBoundingClientRect()` trigger;
   - menu boleh muncul di bawah atau di atas trigger sesuai ruang yang tersedia;
   - jika sisi kanan/kiri tidak cukup, posisi horizontal harus dikoreksi agar tetap berada di viewport.

4. **Jangan hard-code tinggi menu**
   - ukur `menu.offsetHeight` setelah menu dirender;
   - tinggi menu harus mengikuti jumlah action yang sebenarnya.

5. **Jarak dan viewport padding**
   - gunakan gap kecil yang konsisten antara trigger dan menu;
   - sisakan padding dari tepi viewport agar menu tidak menempel/terpotong.

6. **Stacking**
   - gunakan `z-[9999]` atau level z-index global yang disepakati untuk floating action menu;
   - jangan mengandalkan `z-index` rendah di dalam table/card.

7. **Reposition**
   - saat menu terbuka, hitung ulang posisi ketika window resize;
   - hitung ulang ketika halaman/container di-scroll;
   - tutup dengan `Escape`;
   - tutup ketika klik di luar menu/trigger.

8. **Action item**
   - gunakan komponen `<x-ui.action-menu-item>` untuk konsistensi tampilan action;
   - action berbahaya gunakan `variant="danger"`;
   - action positif seperti restore/activate gunakan `variant="success"`;
   - action biasa menggunakan variant default.

### Pola visual standar

```text
[ ... ]
   |
   +--> floating menu
        +----------------+
        | View           |
        | Edit           |
        | Manage Users   |
        | Delete         |
        +----------------+
```

Menu **bukan** bagian dari layout row. Trigger adalah bagian dari row, sedangkan menu dirender melalui teleport ke `body`.

### Pola Alpine minimum

```blade
<div
    x-data="{ open: false, menuTop: 0, menuLeft: 0 }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @resize.window="open && position()"
    @scroll.window="open && position()"
>
    <button x-ref="trigger" type="button" @click="toggle()">
        ...
    </button>

    <template x-teleport="body">
        <div
            x-ref="menu"
            x-show="open"
            class="fixed z-[9999]"
            :style="{ top: `${menuTop}px`, left: `${menuLeft}px` }"
        >
            <!-- x-ui.action-menu-item -->
        </div>
    </template>
</div>
```

Implementasi konkret boleh berbeda selama seluruh behavior wajib di atas tetap terpenuhi.

### Larangan

Jangan membuat pola baru seperti:

```blade
<div class="relative">
    <button>...</button>
    <div class="absolute ...">...</div>
</div>
```

untuk action menu pada table/list jika menu tersebut berpotensi terkena clipping parent.

Jangan membuat setiap halaman memiliki styling/algoritma dropdown `...` sendiri jika sudah ada komponen/pola yang dapat dipakai ulang.

### Checklist review

Sebelum merge halaman baru yang memiliki tombol `...`, pastikan:

- [ ] menu memakai `x-teleport="body"`;
- [ ] menu memakai `fixed`;
- [ ] posisi dihitung dari trigger;
- [ ] posisi atas/bawah menyesuaikan viewport;
- [ ] posisi kiri/kanan dikoreksi terhadap viewport;
- [ ] tinggi menu diukur, bukan di-hard-code;
- [ ] `Escape` dan click-outside bekerja;
- [ ] resize/scroll melakukan reposition;
- [ ] `z-index` cukup tinggi;
- [ ] action item memakai `<x-ui.action-menu-item>`;
- [ ] tidak ada dropdown baru yang terpotong oleh parent `overflow-hidden`.
