# Konvensi Pagination Tabel DANUM

Mulai 29 Agustus 2026, seluruh halaman DANUM yang menampilkan dataset yang dapat bertambah menggunakan pola pagination standar.

## Default

- Nilai awal: **5 baris per halaman**.
- User dapat memilih: **5, 10, 25, atau 50**.
- Selector dan informasi hasil diletakkan di footer tabel.
- Label standar: `Show [N]` dan `Showing X – Y of Z <label>`.
- Navigasi menggunakan komponen `<x-ui.pagination>`.

## Implementasi Livewire

Untuk komponen Livewire/Volt dengan satu tabel utama, gunakan:

```php
use App\Livewire\Concerns\WithStandardTablePagination;

use WithStandardTablePagination;
```

Trait tersebut menyediakan:

```php
public int $perPage = 5;
public function updatedPerPage(): void
```

dan otomatis mereset halaman saat jumlah baris berubah.

Query harus menggunakan:

```php
->paginate($this->perPage)
```

Bukan `->get()` untuk dataset utama.

## Implementasi View

Gunakan satu footer standar:

```blade
<x-ui.table-footer :paginator="$items" label="items" />
```

Jangan memakai `{{ $items->links() }}` langsung pada tabel baru.

## Search dan Filter

Jika tabel memiliki search/filter, perubahan filter harus memanggil `resetPage()` agar user kembali ke halaman pertama.

## Multiple list dalam satu halaman

Jika satu halaman memiliki lebih dari satu paginator, setiap daftar harus memakai page name unik dan page-size property unik. Contoh:

```php
->paginate($pendingPerPage, ['*'], 'pendingPage')
->paginate($perPage, ['*'], 'issuedPage')
```

Footer dapat diarahkan ke property berbeda melalui:

```blade
<x-ui.table-footer
    :paginator="$pendingRequests"
    per-page-model="pendingPerPage"
    label="pengajuan"
/>
```

Komponen `x-ui.pagination` mendukung named page tersebut.

## Pengecualian

Detail page, form, modal, dashboard KPI, dan master list yang memang fixed/sangat kecil tidak wajib berupa tabel berpaginasi. Namun ketika list tersebut berpotensi berkembang, gunakan konvensi ini sejak awal.

## Modul yang sudah distandardisasi

- Users
- Letter Types
- Jabatan / Positions
- Outgoing Letters
- Penarikan Surat
- Audit Log
- Tenant Categories
- Tenant Users dan list user dari detail tenant
- Letter Type Versions
- Letter Type access/permission list

Tujuan konvensi ini adalah agar pagination menjadi bagian dari pola UI DANUM sejak tabel pertama dibuat, bukan tambahan setelah data mulai banyak.
