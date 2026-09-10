# Standar Sorting Tabel DANUM

Semua tabel data yang relevan menggunakan sorting server-side melalui `WithTableSorting`. Setiap komponen wajib menetapkan whitelist kolom yang dapat diurutkan. Klik header mengubah urutan ascending/descending dan mereset pagination ke halaman pertama. Kolom aksi tidak sortable. Sorting dilakukan oleh query database agar tetap efisien pada dataset besar.
