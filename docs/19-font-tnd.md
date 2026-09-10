# DANUM — Standar Font Tata Naskah Dinas

## 1. Font resmi

DANUM membatasi font isi naskah dinas pada dua keluarga font:

- **Arial**, 12 pt;
- **Bookman Old Style**, 12 pt.

Font merupakan bagian dari konfigurasi **Master Jenis Surat**, bukan pilihan user ketika membuat surat.

## 2. Cara kerja

Master Jenis Surat menyimpan `font_family`. Nilai yang diperbolehkan berasal dari enum `LetterFont`:

```text
Arial
Bookman Old Style
```

Saat DOCX dibuat:

1. template dirender seperti biasa;
2. `DocxFontService` memaksa font isi seluruh run menjadi font yang ditentukan jenis surat;
3. ukuran isi dipaksa menjadi 12 pt;
4. kop surat terstruktur kemudian dibuat, sehingga ukuran font kop tetap mengikuti konfigurasi kop (`letterhead_line*_size` dan `letterhead_meta_size`);
5. DOCX hasil dikonversi ke PDF menggunakan LibreOffice.

Dengan urutan ini, aturan 12 pt untuk isi surat tidak merusak ukuran font kop surat.

## 3. Jangan memasukkan font proprietary ke repository

Arial dan Bookman Old Style merupakan font proprietary Microsoft/Monotype. DANUM tidak menyimpan file `.ttf` font tersebut di repository.

Template DOCX hanya menyimpan nama font, sedangkan server/mesin yang menjalankan LibreOffice harus memiliki font yang berlisensi dan terpasang secara lokal.

Microsoft mendokumentasikan file keluarga Arial sebagai `Arial.ttf`, `Arialbd.ttf`, `Arialbi.ttf`, dan `Ariali.ttf`. Bookman Old Style menggunakan antara lain `Bookos.ttf`, `Bookosb.ttf`, `Bookosbi.ttf`, dan `Bookosi.ttf`. Keduanya memiliki informasi lisensi/redistribusi yang harus diperhatikan sebelum dipasang pada server. urlMicrosoft Typography — Arialhttps://learn.microsoft.com/en-gb/typography/font-list/arial urlMicrosoft Typography — Bookman Old Stylehttps://learn.microsoft.com/en-us/typography/font-list/bookman-old-style

## 4. Server PDF

LibreOffice menggunakan font yang tersedia pada sistem operasi. Jika font tambahan belum terpasang, LibreOffice dapat menggunakan font pengganti. Karena itu environment yang menghasilkan PDF DANUM wajib menyediakan Arial dan Bookman Old Style.

LibreOffice mendukung font TrueType (`.ttf`) dan OpenType (`.otf`) yang dipasang pada sistem operasi dan kemudian tersedia untuk modul LibreOffice. urlLibreOffice — Adding fontshttps://books.libreoffice.org/en/GS262/GS26213-CustomizingLO.html

Jangan mengatasi font yang hilang dengan mengubah konfigurasi DANUM menjadi font pengganti. Jika TND menetapkan Arial atau Bookman Old Style, pasang font yang benar pada environment PDF.

## 5. Prinsip desain

- User OPD tidak memilih font ketika membuat surat.
- Administrator TND menentukan font pada Master Jenis Surat.
- Hanya Arial dan Bookman Old Style yang tersedia.
- Isi naskah dinas selalu 12 pt.
- Kop surat tetap memiliki ukuran tersendiri sesuai konfigurasi kop.
- Jangan menyimpan font proprietary di repository tanpa dasar lisensi yang jelas.
- Preview dan PDF harus mengikuti konfigurasi font yang sama.

## 6. Fresh migration

Field `font_family` ditambahkan langsung ke baseline `create_letter_types_table` karena DANUM masih development. Jangan membuat migration patch tambahan untuk field ini selama baseline masih dapat diperbaiki.

Setelah perubahan schema, smoke test tetap:

```bash
php artisan migrate:fresh
php artisan db:seed
php artisan test
```

Catatan: `migrate:fresh` dan test harus dijalankan pada environment repository DANUM yang memiliki PostgreSQL, PHP, dependency Composer, dan LibreOffice. Tooling saat perubahan ini tidak mengeksekusi database lokal pengguna.
