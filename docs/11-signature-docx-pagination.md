# Perbaikan Pagination Tanda Tangan DOCX

## Konteks

Pada proses render template surat DOCX ke PDF, blok tanda tangan elektronik (TTE) sempat mengalami masalah pagination. Pada hasil PDF, bagian tanda tangan dapat terpecah antar halaman atau seluruh blok tanda tangan terdorong ke halaman berikutnya sehingga meninggalkan ruang kosong besar di halaman sebelumnya.

Contoh struktur signature table yang menjadi perhatian:

- tanggal dan lokasi;
- `{{qr}}`;
- `{{jabatan_ttd}}`;
- `{{tte}}`;
- `{{nama_ttd}}`;
- `{{pangkat_ttd}}`;
- `{{golongan_ttd}}`;
- `{{nip_ttd}}`.

## Diagnosis

Implementasi awal pada `DocxRendererService` menambahkan dua perlindungan:

1. `w:cantSplit` pada setiap baris tabel tanda tangan agar satu baris tidak terpotong antar halaman.
2. `w:keepLines` dan `w:keepNext` pada paragraf-paragraf di dalam tabel.

`w:keepNext` ternyata terlalu agresif. Karena properti tersebut membuat satu paragraf harus tetap bersama paragraf berikutnya, seluruh rangkaian signature table dapat dianggap sebagai satu blok yang harus dipindahkan sekaligus ke halaman berikutnya. Akibatnya, signature tidak terpecah, tetapi halaman sebelumnya menyisakan ruang kosong yang tidak semestinya.

## Solusi yang diterapkan

Pada `app/Services/DocxRendererService.php`:

- **Tetap gunakan `w:cantSplit`** untuk setiap `<w:tr>` pada signature table.
- **Tetap gunakan `w:keepLines`** pada setiap paragraf signature.
- **Hapus penggunaan `w:keepNext`** pada signature table.
- Dengan demikian LibreOffice tetap menjaga baris dan isi paragraf tetap utuh, tetapi bebas menempatkan blok tanda tangan pada ruang halaman yang tersedia.

Prinsipnya:

```text
cantSplit  = cegah satu BARIS tabel pecah antar halaman
keepLines  = cegah satu PARAGRAF pecah antar halaman
keepNext   = jangan digunakan pada seluruh blok signature
```

## Test

`tests/Unit/DocxTemplateRepeaterTest.php` memiliki test khusus untuk signature table:

`test_signature_table_rows_are_protected_from_page_splitting()`

Test tersebut memastikan:

- setiap baris signature mendapat `w:cantSplit`;
- data penandatangan tetap ter-render;
- NIP penandatangan tetap ter-render;
- signature table tidak lagi menggunakan rangkaian `w:keepNext` yang menyebabkan seluruh blok terdorong ke halaman berikutnya.

## Commit terkait

Perbaikan terakhir dilakukan langsung pada branch `master`:

- `255dcfc9c6c1a3f3ed9beb1b9e9876b28d17d089` — `avoid pushing signature block to next page`
- `2ce65c63df371e9045f9b81f6f594fe56a2f7cb2` — `test signature block pagination`

## Status

Perubahan kode sudah diterapkan di `master`. Hasil PDF lama tidak otomatis berubah; surat harus **di-generate ulang dari template DOCX** untuk menguji pagination terbaru.

Jika signature masih pindah ke halaman berikutnya setelah perubahan ini, pemeriksaan berikutnya adalah XML DOCX hasil render, khususnya:

- `w:br` dengan tipe `page`;
- `w:pageBreakBefore`;
- tinggi baris (`w:trHeight`);
- vertical merge pada cell signature;
- kemungkinan page break eksplisit tepat sebelum signature table.

Pemeriksaan tersebut dilakukan hanya jika hasil render terbaru masih menunjukkan pagination yang tidak sesuai.
