# DANUM — Testing Strategy

**Status:** Aktif  
**Scope:** Feature/business regression suite

## 1. Tujuan

Test DANUM harus melindungi **business rule, authorization, tenant isolation, dan historical integrity**.

Test bukan dibuat hanya berdasarkan jumlah method.

## 2. Database testing

Testing menggunakan PostgreSQL, bukan SQLite, agar engine testing sesuai dengan workflow DANUM dan constraint database yang digunakan.

Database test harus terpisah dari database development/production.

Konfigurasi saat ini diarahkan ke:

```text
DB_CONNECTION=pgsql
APP_ENV=testing
APP_TIMEZONE=Asia/Pontianak
```

## 3. Baseline saat dokumen ini dibuat

Regression suite telah mencapai **49 passing tests** setelah workflow Outgoing Letter dan public verification diperbaiki.

Angka ini adalah baseline saat ini dan harus bertambah atau tetap terjaga ketika fitur dikembangkan.

## 4. Struktur test

Arah struktur:

```text
tests/Feature/
├── Authorization/
│   ├── RoleAccessTest.php
│   ├── TenantIsolationTest.php
│   └── TenantProfileAuthorizationTest.php
├── Users/
│   └── UserManagementTest.php
├── Tenants/
│   └── TenantServiceTest.php
└── OutgoingLetters/
    ├── OutgoingLetterWorkflowTest.php
    └── VerificationTest.php
```

Struktur dapat berubah mengikuti modul final, tetapi pengelompokan harus berdasarkan business domain.

## 5. Prioritas assertion

Prioritas assertion:

1. database state;
2. authorization;
3. tenant isolation;
4. business state/transition;
5. response behavior;
6. UI text hanya jika memang merupakan behavior UI yang perlu dikunci.

Jangan membuat test terlalu bergantung pada wording UI jika business state dapat diuji langsung.

## 6. Authorization matrix

Minimal test:

- Super Admin lintas tenant sesuai policy;
- Tenant Admin pada tenant sendiri;
- Tenant Admin ditolak pada tenant lain;
- Tenant User tidak dapat melakukan administrative action;
- object policy dan tenant isolation diuji secara terpisah.

## 7. User regression

Wajib dipertahankan:

- create user;
- update tanpa mengganti email sendiri;
- update ke email baru;
- duplicate email milik user lain ditolak;
- tenant isolation;
- role authorization.

## 8. TND configuration tests

Ketika model permission TND sudah diimplementasikan, wajib ada test:

- active document type muncul untuk unit yang diizinkan;
- inactive document type tidak tersedia untuk surat baru;
- unit tanpa permission ditolak backend;
- perubahan permission tidak mengubah surat historis;
- administrator TND dapat mengubah configuration sesuai policy.

## 9. Template version tests

Wajib ada test:

- surat baru mengambil template version aktif;
- versi baru tidak mengubah surat lama;
- template version yang sudah dipakai dapat direferensikan kembali;
- snapshot historis tetap konsisten jika snapshot sudah diimplementasikan.

## 10. Outgoing Letter tests

Wajib mencakup lifecycle:

```text
DRAFT
 -> SUBMITTED
 -> VALIDATED
 -> ISSUED
```

dan jalur:

```text
REJECTED
CANCELLED
WITHDRAWAL PENDING
WITHDRAWN
```

Masa berlaku juga wajib diuji untuk seluruh configured periods.

## 11. Verification tests

Public verification wajib menguji:

- active;
- expired;
- not yet active;
- withdrawn;
- unknown token;
- unissued document.

## 12. Timezone regression

Karena sistem menggunakan `Asia/Pontianak`, test waktu harus eksplisit.

Contoh:

```php
Carbon::setTestNow(
    Carbon::parse('2026-08-25 10:20:00', 'Asia/Pontianak')
);
```

Pastikan test membersihkan `setTestNow()` setelah selesai.

## 13. Rule saat menambah fitur

Setiap business rule baru harus diikuti regression test.

Urutan yang disarankan:

```text
Business rule
   -> implementation
   -> feature test
   -> full test suite
```

Jangan mengubah assertion hanya untuk membuat test hijau jika behavior aplikasi memang belum sesuai business rule.

## 14. Test reset policy

Test lama hanya boleh dihapus jika:

1. behavior memang obsolete;
2. penggantinya sudah diuji;
3. coverage business rule tidak hilang.

Reset suite dilakukan bertahap, bukan menghapus seluruh regression baseline sekaligus.
