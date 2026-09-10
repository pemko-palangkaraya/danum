# Standar Sorting Tabel DANUM

## Tujuan

Semua tabel data DANUM yang relevan menggunakan sorting server-side melalui concern `WithTableSorting` dan komponen `x-ui.table-sort-header`.

## Aturan

- Sorting dilakukan di database, bukan hanya pada baris yang sedang tampil.
- Setiap Livewire table mendefinisikan whitelist `$sortableColumns`.
- Kolom aksi, checkbox, dan kolom non-data tidak dibuat sortable.
- Klik header sekali mengurutkan ascending; klik lagi membalik ke descending.
- Mengganti kolom sorting mengatur arah awal ke ascending.
- Sorting mengembalikan pagination ke halaman pertama.
- Default sorting tetap ditentukan per tabel sesuai kebutuhan bisnis.
- Relasi menggunakan expression/query yang aman dan tidak menerima nama kolom mentah dari pengguna.

## UI

Header sortable menggunakan:

```blade
<x-ui.table-sort-header
    column="name"
    label="Nama"
    :sort-by="$sortBy"
    :sort-direction="$sortDirection"
/>
```

## Implementasi

Concern: `app/Livewire/Concerns/WithTableSorting.php`

Komponen: `resources/views/components/ui/table-sort-header.blade.php`

Tabel yang sudah memakai pagination standar tetap menggunakan `WithStandardTablePagination`; sorting menjadi concern tambahan.

## Prinsip

Jangan membuat sorting ad-hoc per halaman jika pola yang sama dapat dipakai kembali. Untuk tabel baru, tentukan whitelist kolom sortable sejak awal dan pilih default order yang paling sesuai dengan konteks data.
