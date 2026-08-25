# DANUM — Template, Versioning, dan Historical Integrity

**Status:** Draft arsitektur aktif  
**Scope:** Template surat dan dokumen historis

## 1. Prinsip

Template menentukan **bagaimana surat terlihat**. Data surat menentukan **apa isi surat**.

Jangan mencampurkan keduanya.

```text
Template
  -> layout
  -> kop
  -> struktur
  -> typography
  -> elemen tetap

Letter Data
  -> penerima
  -> tanggal
  -> perihal
  -> isi
  -> pihak yang ditugaskan
  -> data dinamis lain
```

## 2. Model template

Konsep:

```text
Document Type / Letter Type
        |
        v
     Template
        |
        +-- Version 1
        +-- Version 2
        +-- Version 3
```

Setiap version minimal memiliki:

- nomor versi;
- status;
- mulai berlaku;
- akhir berlaku bila ada;
- template/content yang digunakan;
- pembuat/perubah;
- timestamp perubahan;
- catatan perubahan.

## 3. Template aktif

Ketika user membuat surat baru:

```text
Document Type
    -> resolve active Template Version
    -> attach version to new letter
```

User OPD tidak memilih versi secara bebas.

## 4. Contoh perubahan template

```text
Surat Undangan v1
berlaku sampai 31 Aug 2026

Surat Undangan v2
mulai berlaku 1 Sep 2026
```

Surat baru mulai 1 September menggunakan v2.

Surat lama tetap menggunakan v1.

## 5. Historical integrity

Surat yang sudah dibuat/diterbitkan harus tetap dapat direkonstruksi berdasarkan konfigurasi yang digunakan ketika surat tersebut dibuat/diterbitkan.

Minimal surat menyimpan reference ke:

```text
letter_type_id
letter_type_version_id / template_version_id
```

Arah arsitektur yang direkomendasikan untuk tahap berikutnya adalah menyimpan snapshot konfigurasi yang relevan pada saat finalisasi/penerbitan:

```text
OutgoingLetter
  +-- template_version_id
  +-- template_snapshot
  +-- configuration_snapshot
```

Snapshot digunakan untuk menjaga auditability ketika master TND berubah di masa depan.

## 6. Jangan mutate histori

Jangan melakukan:

```text
Template v1 -> edit isi v1
```

jika v1 sudah digunakan oleh dokumen historis.

Lebih aman:

```text
v1 -> immutable setelah digunakan
v2 -> perubahan baru
```

Jika kebutuhan bisnis mengharuskan koreksi template yang belum digunakan, tetap pastikan tidak ada dokumen historis yang bergantung pada hasil yang berubah.

## 7. Pemisahan data dan template

Contoh:

Template menentukan:

- posisi kop;
- margin;
- judul;
- struktur nomor;
- posisi tanda tangan;
- footer.

Data menentukan:

- nama penerima;
- alamat;
- tanggal;
- perihal;
- isi;
- pejabat.

## 8. Dampak terhadap OutgoingLetter

Saat surat diterbitkan, sistem harus dapat menjawab:

1. Jenis surat apa yang digunakan?
2. Template version apa yang digunakan?
3. Konfigurasi TND apa yang berlaku?
4. Siapa yang membuat/mengubah/menerbitkan?
5. Kapan konfigurasi tersebut berlaku?

## 9. Prinsip perubahan

Perubahan master TND berlaku untuk **surat baru** sesuai tanggal/status konfigurasi.

Perubahan master tidak boleh mengubah tampilan, status, atau makna historis surat yang sudah diterbitkan.
