# DANUM — Workflow Surat Keluar

**Status:** Draft arsitektur aktif  
**Scope:** Lifecycle Surat Keluar

## 1. Tujuan

Workflow harus mencerminkan aturan bisnis surat, bukan sekadar urutan method service.

## 2. Alur utama

```text
DRAFT
  |
  v
SUBMITTED
  |
  v
VALIDATED
  |
  v
ISSUED
```

Alur dapat memiliki jalur penolakan/pembatalan sesuai aturan yang sudah diterapkan oleh aplikasi.

## 3. Draft

Draft adalah surat yang masih dapat dikerjakan oleh pembuat sesuai authorization.

Data draft dapat diperbaiki sebelum masuk tahap yang mengunci data.

## 4. Submit

Submit menandakan draft diajukan untuk proses berikutnya.

Setelah submit, aturan edit/delete harus mengikuti status dan policy. User tidak boleh mengubah data melalui endpoint langsung untuk melewati pembatasan UI.

## 5. Validation

Validation memastikan surat memenuhi rule sebelum diterbitkan.

Hasil validasi dapat:

```text
VALIDATED
```

atau kembali ke draft/rejected sesuai workflow yang berlaku.

## 6. Issue

Penerbitan adalah titik penting historical integrity.

Saat issue, sistem menetapkan atau memastikan:

- status `ISSUED`;
- `issued_at`;
- `valid_from`;
- `valid_until` jika jenis surat memiliki expiry;
- verification token;
- status history.

## 7. Masa berlaku

Konsep status bisnis:

```text
ISSUED + valid_from sudah tiba + belum expired
    -> ACTIVE

ISSUED + valid_until sudah lewat
    -> EXPIRED

ISSUED + valid_from masih future
    -> NOT_YET_ACTIVE

WITHDRAWN
    -> WITHDRAWN
```

`expired` adalah hasil perhitungan berdasarkan tanggal/waktu masa berlaku. Jangan mengubah status database `ISSUED` hanya karena surat sudah expired jika business rule memang memisahkan lifecycle status dari verification state.

## 8. Validity period

Jenis surat dapat memiliki konfigurasi:

- none;
- 1 week;
- 2 weeks;
- 1 month;
- 3 months;
- 6 months;
- 1 year;
- periode lain yang ditentukan configuration.

Perhitungan `valid_until` harus berasal dari waktu penerbitan/valid_from dan konfigurasi jenis surat.

## 9. Withdrawal

Konsep:

```text
ISSUED
   |
   v
Withdrawal Request (PENDING)
   |
   +----> APPROVED -> WITHDRAWN
   |
   +----> REJECTED -> tetap ISSUED
```

Request withdrawal harus memiliki alasan dan dokumen/statement yang diwajibkan oleh business rule.

Approval/rejection harus menyimpan actor, waktu, dan decision note sesuai schema.

## 10. Status history

Perubahan status penting harus dapat ditelusuri.

Minimal histori mencatat:

- surat;
- status;
- action;
- actor;
- timestamp.

History tidak boleh menjadi pengganti current state. Current state berada pada `OutgoingLetter`; history menjadi audit trail.

## 11. Timezone

Timezone aplikasi DANUM untuk deployment saat ini adalah **Asia/Pontianak (UTC+7)**.

Semua business rule waktu harus konsisten dengan timezone aplikasi.

Test workflow wajib menggunakan timezone yang sama dan/atau `Carbon::setTestNow()` agar regression test deterministik.

## 12. Public verification

Public verification menggunakan verification token.

State yang harus dapat dibedakan:

```text
ISSUED + current
    -> active

ISSUED + expired
    -> expired

ISSUED + future valid_from
    -> not_yet_active

WITHDRAWN
    -> withdrawn

unknown/unissued token
    -> not verified
```

Expired dan withdrawn tetap merupakan dokumen yang dapat diverifikasi sebagai dokumen yang pernah diterbitkan, bukan otomatis menjadi `not found`.

## 13. Prinsip authorization

Semua transition harus diperiksa di backend.

Contoh:

```text
UI menyembunyikan tombol Issue
        !=
backend mengizinkan issue
```

Backend harus tetap menolak transition jika actor tidak berwenang.
