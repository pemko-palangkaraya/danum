# DANUM — Implementasi Template Versioning

**Status:** Selesai — 25 Agustus 2026

## Tujuan

Setiap perubahan template TND menghasilkan version baru dan tidak memutasi versi yang telah dipakai dokumen.

## Aturan

- `LetterType` adalah master jenis surat.
- `LetterTypeVersion` menyimpan snapshot template per versi.
- `OutgoingLetter` harus mereferensikan versi yang digunakan saat surat dibuat/finalisasi.
- User OPD tidak memilih versi secara bebas.
- Versi aktif dipilih sistem berdasarkan konfigurasi/effective period.
- Versi historis tidak boleh berubah jika sudah dipakai surat.
- File template lama dipertahankan agar snapshot historis tetap dapat direkonstruksi.
- Pembuatan versi baru wajib memiliki catatan perubahan.
- Periode versi tidak boleh bertumpang tindih.

## Implementasi

```text
LetterType
  -> active/current version
  -> template resolution
  -> LetterTypeVersion
  -> OutgoingLetter.letter_type_version_id
  -> immutable historical template file
  -> audit log
```

Super Admin memiliki halaman pengelolaan versi dari menu Letter Types. Halaman tersebut menampilkan versi historis, periode efektif, pembuat, dan catatan perubahan, serta menyediakan pembuatan versi baru dengan cross-check placeholder DOCX terhadap variabel Letter Type.

## Acceptance criteria

1. Membuat Letter Type dengan template menghasilkan version awal.
2. Mengubah template menghasilkan version berikutnya.
3. Mengubah metadata tanpa mengubah template tidak membuat version palsu.
4. Surat baru menggunakan version aktif.
5. Surat lama tetap menunjuk version lama.
6. Test memastikan perubahan template tidak mengubah histori.
7. Versi lama tidak dihapus ketika template baru diunggah.
8. Pembuatan version baru tercatat pada Audit Log.
9. User non-Super Admin tidak dapat mengelola version global.
