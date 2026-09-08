# Konvensi Validasi & UX Form Danum

> Catatan hasil audit dan implementasi validasi form. Berlaku sebagai konvensi pengembangan form Danum ke depan.

## Tujuan

Setiap form harus memberikan umpan balik yang jelas ketika input belum lengkap atau tidak valid, sekaligus memudahkan pengguna langsung memperbaiki field yang bermasalah.

## Konvensi Utama

### 1. Backend validation adalah sumber kebenaran

Aturan wajib/tidak wajib harus mengikuti validasi backend. Jangan membuat status `required` di UI yang bertentangan dengan rule backend.

### 2. Field wajib diberi tanda bintang merah

Setiap field yang memiliki rule `required` di backend harus menampilkan tanda `*` berwarna merah pada label.

Komponen `x-ui.field` sudah mendukung prop `required` dan menampilkan indikator tersebut.

### 3. Field wajib menggunakan HTML `required`

Selain indikator visual, field wajib harus meneruskan `required` ke elemen HTML native (`input`, `select`, atau `textarea`).

Tujuannya agar browser juga memahami bahwa field tersebut wajib diisi dan UX validasi tetap konsisten.

### 4. Error validasi harus auto-focus

Ketika validasi Livewire gagal, sistem harus:

1. Mendeteksi field yang mengalami error.
2. Mencari field input/select/textarea yang terkait.
3. Memberikan fokus ke field error pertama.
4. Melakukan scroll agar field tersebut terlihat jika berada di luar viewport.

Implementasi dibuat secara global di `resources/js/app.js`, sehingga tidak perlu menulis logic autofocus berulang pada setiap halaman.

### 5. Error state harus reusable dan semantik

Komponen field/input/select/textarea menggunakan penanda error yang konsisten agar logic JavaScript global dapat mengenali field bermasalah.

Error message menggunakan `role="alert"`, sedangkan indikator bintang merah diberi `aria-hidden="true"` agar tidak mengganggu pembacaan label oleh assistive technology.

### 6. Conditional validation harus tercermin di UI

Jika sebuah field hanya wajib pada kondisi tertentu, indikator `*` dan atribut HTML `required` juga harus mengikuti kondisi tersebut.

Contoh:

- Password wajib saat membuat user baru.
- Password tidak wajib saat mengedit user yang sudah ada.
- `tenant_id` dapat menjadi wajib berdasarkan platform role.
- `custom_role_id` dapat menjadi wajib ketika tenant dipilih.

### 7. Jangan duplikasi logic validasi/autofocus per halaman

Gunakan komponen UI bersama dan logic global sebisa mungkin. Form baru cukup menghubungkan status `required` berdasarkan rule backend/condition yang berlaku.

## Alur Standar Form

Setiap form baru atau form yang direfactor mengikuti alur berikut:

```text
Backend validation
        ↓
Required marker (*)
        ↓
HTML required
        ↓
Error state
        ↓
Autofocus field error pertama
        ↓
Scroll ke field error
```

## Komponen yang Sudah Disesuaikan

- `resources/views/components/ui/field.blade.php`
- `resources/views/components/ui/input.blade.php`
- `resources/views/components/ui/select.blade.php`
- `resources/views/components/ui/textarea.blade.php`
- `resources/js/app.js`

## Form yang Sudah Disesuaikan Dalam Audit Ini

### Population / Citizens

Field yang backend-nya wajib:

- NIK
- Nama Lengkap
- Kewarganegaraan
- Status Kependudukan

Field-field tersebut harus menampilkan indikator wajib dan menggunakan HTML `required`.

### Users

Password dibuat dinamis:

- Create user → wajib, mengikuti validasi `required|min:8`.
- Edit user → tidak wajib apabila tidak sedang mengganti password.

## Catatan Audit

Audit pada percakapan ini menemukan bahwa komponen UI sebenarnya sudah memiliki dukungan prop `required`, tetapi sebelumnya beberapa komponen belum meneruskan prop tersebut ke elemen HTML native. Selain itu, indikator wajib hanya muncul jika caller secara eksplisit mengirim `required`.

Perbaikan yang dilakukan memperkuat komponen bersama dan menambahkan autofocus validasi secara global.

**Catatan penting:** audit ini belum berarti seluruh form di repository sudah diperiksa satu per satu. Konvensi di atas menjadi standar yang harus digunakan saat audit/refactor form berikutnya.

## Standar Untuk Pekerjaan Berikutnya

Saat menemukan atau membuat form baru, lakukan pemeriksaan berurutan:

1. Cari validation rule backend.
2. Identifikasi field `required` dan conditional required.
3. Pastikan label menampilkan `*` merah.
4. Pastikan native element menerima `required`.
5. Pastikan error ditampilkan melalui komponen UI yang konsisten.
6. Pastikan error pertama dapat menerima autofocus dan scroll.
7. Hindari implementasi JavaScript validasi/autofocus yang sama secara lokal di banyak halaman.
8. Jika menemukan kode yang benar-benar tidak digunakan dan hanya tersisa untuk testing lama, boleh dipertimbangkan untuk dihapus setelah dipastikan tidak memiliki dependency.
9. Perubahan tetap dikerjakan langsung pada `master`, sesuai workflow project saat ini.
