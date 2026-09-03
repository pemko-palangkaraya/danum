# Repeater pada Template Surat

Danum mendukung field berulang untuk surat yang memuat lebih dari satu orang, barang, atau entri lain.

## Definisi variabel

Pada master jenis surat, tambahkan satu baris:

```text
@repeat pelaksana|Nama:nama,NIP:nip,Jabatan:jabatan
```

Formatnya:

```text
@repeat nama_collection|Label field:key_field,Label field 2:key_field_2
```

Definisi ini tetap kompatibel dengan variable surat lama yang berupa nama field biasa.

## Marker DOCX

Untuk blok teks, gunakan:

```text
{{#pelaksana}}
Nama: {{nama}}
NIP: {{nip}}
Jabatan: {{jabatan}}
{{/pelaksana}}
```

Untuk tabel DOCX, marker pembuka dan penutup dapat ditempatkan pada satu baris tabel. Danum akan menggandakan baris tersebut sesuai jumlah item.

## Data yang disimpan

`OutgoingLetter.input_data` tetap berupa JSON. Contoh:

```json
{
  "pelaksana": [
    {"nama": "Budi Santoso", "nip": "19800001", "jabatan": "Kasi Pemerintahan"},
    {"nama": "Ahmad Fauzi", "nip": "19800002", "jabatan": "Staf"}
  ]
}
```

Dengan pola ini, satu template dapat menangani satu atau banyak pelaksana tanpa membuat template khusus untuk setiap jumlah orang.
