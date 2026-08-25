# DANUM — Model TND, Jenis Surat, dan Kewenangan

**Status:** Draft arsitektur aktif  
**Scope:** Master TND dan authorization jenis surat

## 1. Tujuan

Dokumen ini mendefinisikan bagaimana DANUM menentukan jenis surat yang dapat digunakan oleh suatu unit tanpa hard-code.

## 2. Entitas konseptual

### Organization / Tenant

Representasi unit/organisasi yang menggunakan DANUM. Implementasi saat ini menggunakan `Tenant`.

Struktur organisasi dapat berkembang menjadi hierarkis bila kebutuhan resmi pemerintah daerah mengharuskannya.

Contoh:

```text
Pemerintah Daerah
  +-- Sekretariat Daerah
  |     +-- Bagian
  |     +-- Subbagian
  +-- Dinas
  +-- Badan
  +-- Kecamatan
        +-- Kelurahan
```

Struktur tersebut adalah contoh konseptual, bukan daftar organisasi yang harus di-hard-code.

### Document Type / Letter Type

Master jenis surat yang dapat dibuat.

Contoh:

- Surat Dinas
- Surat Undangan
- Surat Tugas
- Surat Keterangan
- Surat Pengantar
- Surat Permohonan

Jenis surat harus memiliki minimal identitas, kode, deskripsi, status aktif/nonaktif, dan konfigurasi yang diperlukan untuk proses pembuatannya.

### Document Type Permission

Hubungan antara organisasi/unit dan jenis surat.

Konsep:

```text
Organization A + Surat Tugas = allowed
Organization B + Surat Tugas = denied
```

Status permission harus menjadi data.

## 3. Matriks kewenangan

Contoh konseptual:

| Unit | Jenis Surat | Status |
|---|---|---|
| Sekretariat Daerah | Surat Dinas | Aktif |
| Sekretariat Daerah | Surat Tugas | Aktif |
| Dinas | Surat Dinas | Aktif |
| Kecamatan | Surat Tugas | Aktif |
| Kelurahan | Surat Tugas | Tidak Aktif |

Daftar di atas hanya contoh. Nilai sebenarnya harus berasal dari konfigurasi administrator TND.

## 4. Resolusi jenis surat untuk user

Ketika user membuka `Buat Surat Keluar`, sistem harus melakukan konsep berikut:

```text
current user
    -> organization / tenant
    -> active document type permissions
    -> active document types
    -> daftar jenis surat yang boleh digunakan
```

Jenis surat yang tidak diizinkan tidak ditampilkan.

Tetapi filtering UI **bukan satu-satunya security control**. Jika request dibuat secara manual dengan `document_type_id` yang tidak diizinkan, backend harus menolak.

## 5. Authorization

Aturan dasar:

```text
User
  -> Role
  -> Organization
  -> Document Type Permission
  -> Document Type
```

Policy aplikasi tetap menjadi lapisan authorization. Permission TND menjadi aturan domain tambahan.

## 6. Status jenis surat

Jenis surat minimal membutuhkan lifecycle konfigurasi:

```text
ACTIVE
INACTIVE
```

Jenis surat nonaktif tidak boleh dipilih untuk surat baru.

Nonaktif tidak berarti dokumen lama dihapus atau menjadi tidak valid.

## 7. Perubahan kewenangan

Jika administrator mengubah:

```text
Kelurahan + Surat Tugas
Tidak Diizinkan
```

menjadi:

```text
Kelurahan + Surat Tugas
Diizinkan
```

maka perubahan berlaku untuk pembuatan surat baru setelah konfigurasi aktif.

Surat yang sudah dibuat sebelumnya tidak boleh berubah hanya karena permission berubah.

## 8. Aturan penting

- Jangan hard-code nama organisasi.
- Jangan hard-code daftar jenis surat per organisasi.
- Jangan mengandalkan hidden menu sebagai security.
- Jangan menghapus histori surat ketika jenis surat dinonaktifkan.
- Jangan mengganti template historis dokumen lama hanya karena konfigurasi baru aktif.

## 9. Pengembangan berikutnya

Model ini dapat dikembangkan untuk aturan:

- pejabat penandatangan;
- validator;
- unit pembuat;
- pembatasan berdasarkan jenis organisasi;
- masa berlaku permission;
- approval policy.

Aturan tambahan harus ditambahkan sebagai konfigurasi domain, bukan conditional berdasarkan nama unit di source code.
