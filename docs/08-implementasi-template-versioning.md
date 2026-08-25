# DANUM — Implementasi Template Versioning

**Status:** Aktif

## Tujuan

Setiap perubahan template TND menghasilkan version baru dan tidak memutasi versi yang telah dipakai dokumen.

## Aturan

- `LetterType` adalah master jenis surat.
- `LetterTypeVersion` menyimpan snapshot template per versi.
- `OutgoingLetter` harus mereferensikan versi yang digunakan saat surat dibuat/finalisasi.
- User OPD tidak memilih versi secara bebas.
- Versi aktif dipilih sistem berdasarkan konfigurasi/effective period.
- Versi historis tidak boleh berubah jika sudah dipakai surat.

## Acceptance criteria

1. Membuat Letter Type dengan template menghasilkan version awal.
2. Mengubah template menghasilkan version berikutnya.
3. Mengubah metadata tanpa mengubah template tidak membuat version palsu.
4. Surat baru menggunakan version aktif.
5. Surat lama tetap menunjuk version lama.
6. Test memastikan perubahan template tidak mengubah histori.

## Next implementation

```text
LetterType
  -> active/current version
  -> template resolution
  -> OutgoingLetter version reference
  -> immutable historical snapshot
```
