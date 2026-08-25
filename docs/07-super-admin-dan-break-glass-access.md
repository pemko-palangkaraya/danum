# DANUM — Super Admin dan Break-Glass Access

**Status:** Kebijakan arsitektur aktif  
**Scope:** System-wide recovery, administration, dan emergency access

## 1. Prinsip utama

DANUM harus memiliki **satu role sistem dengan kewenangan tertinggi: `Super Admin`**.

Tujuannya bukan agar semua pekerjaan dilakukan oleh Super Admin, tetapi agar sistem selalu memiliki actor yang dapat menyelesaikan masalah administratif lintas tenant ketika terjadi kondisi yang tidak dapat diselesaikan oleh actor biasa.

Contoh kondisi:

- Tenant Admin kehilangan akses;
- user salah konfigurasi role;
- permission jenis surat salah;
- tenant tidak sengaja dikunci;
- konfigurasi TND perlu dipulihkan;
- perlu investigasi lintas tenant;
- workflow tersangkut dan membutuhkan intervention sesuai policy;
- terjadi masalah operasional yang membutuhkan administrator sistem.

## 2. Super Admin bukan user operasional biasa

Super Admin adalah **system-level authority**.

Kemampuan konseptual:

```text
Super Admin
  |
  +-- semua Tenant
  +-- semua User
  +-- semua Document Type
  +-- semua TND Configuration
  +-- semua Outgoing Letter (sesuai policy)
  +-- semua Verification / investigation
  +-- system administration
```

Detail action tetap harus melewati policy. `Super Admin` tidak boleh menjadi alasan untuk melewati audit trail.

## 3. Break-glass access

Super Admin berfungsi sebagai **break-glass / recovery actor** ketika actor normal tidak mampu menyelesaikan masalah.

Contoh:

```text
Tenant Admin
    ↓
Tidak dapat memperbaiki masalah
    ↓
Super Admin
    ↓
Intervention
    ↓
Audit trail
```

Intervention tidak boleh menghapus histori masalah.

## 4. Tidak boleh ada dead-end authorization

Sebuah konfigurasi tidak boleh membuat sistem kehilangan semua actor yang dapat memperbaikinya.

Contoh yang harus dicegah:

```text
Tenant Admin kehilangan akses
       ↓
Tidak ada admin lain
       ↓
Tidak ada Super Admin
       ↓
Sistem tidak dapat dipulihkan
```

Karena itu Super Admin harus tetap memiliki akses lintas tenant untuk recovery.

## 5. Super Admin dan TND Admin

Jangan menyamakan role:

```text
Super Admin == TND Admin
```

Keduanya berbeda secara tanggung jawab.

```text
Super Admin
  = system authority / recovery

TND Admin / Ortal
  = TND configuration authority

Tenant Admin
  = tenant operational authority

Tenant User
  = operational user
```

Super Admin boleh memiliki seluruh capability TND Admin, tetapi arsitektur tidak boleh bergantung pada Super Admin untuk pekerjaan TND sehari-hari.

## 6. Audit wajib

Setiap tindakan Super Admin yang mengubah data penting harus memiliki actor yang dapat ditelusuri.

Minimal audit harus dapat menjawab:

- siapa actor;
- apa action;
- object apa yang diubah;
- tenant mana yang terdampak;
- nilai sebelum/sesudah bila relevan;
- kapan dilakukan;
- alasan bila action bersifat recovery/intervention.

## 7. Tidak boleh anonymous/system actor untuk perubahan manual

Untuk tindakan administratif yang dilakukan manusia, jangan menyamarkan actor sebagai:

```text
system
admin
unknown
```

jika user sebenarnya diketahui.

Gunakan user Super Admin yang melakukan action.

## 8. Super Admin harus lintas tenant

Super Admin tidak boleh dibatasi oleh `tenant_id` seperti Tenant Admin.

Contoh:

```text
Tenant A
Tenant B
Tenant C

Super Admin
    ↓
view/update/investigate sesuai policy
semuanya
```

Ini harus menjadi regression test.

## 9. Proteksi terhadap self-lockout

Sistem sebaiknya mencegah konfigurasi yang membuat seluruh Super Admin kehilangan akses secara tidak sengaja.

Minimal rule yang perlu dipertimbangkan:

- tidak boleh menonaktifkan Super Admin terakhir;
- tidak boleh menghapus Super Admin terakhir;
- perubahan role Super Admin terakhir harus memiliki safeguard;
- perubahan critical system permission harus dicatat.

Detail implementasi ditentukan saat modul system administration dikembangkan.

## 10. Credential security

Kewenangan tinggi tidak berarti credential boleh diperlakukan longgar.

Arah pengembangan:

- password policy;
- session security;
- optional MFA jika tersedia;
- audit login;
- audit failed login;
- recovery procedure;
- periodic review account Super Admin.

## 11. Emergency workflow

Untuk tindakan yang sangat sensitif, arah pengembangan dapat menggunakan:

```text
Super Admin
  ↓
Reason required
  ↓
Confirmation
  ↓
Action
  ↓
Audit event
```

Bukan:

```text
click
↓
langsung mutate data
```

## 12. Test requirements

Regression test wajib memastikan:

- Super Admin dapat mengakses data lintas tenant;
- Super Admin dapat memperbaiki user/tenant sesuai policy;
- Super Admin dapat mengelola master TND sesuai capability;
- Tenant Admin tidak memperoleh akses Super Admin;
- Tenant User tidak memperoleh administrative access;
- action Super Admin tetap menghasilkan audit actor;
- Super Admin terakhir tidak dapat di-lock-out secara tidak sengaja setelah safeguard diimplementasikan.

## 13. Prinsip final

> **Harus selalu ada actor yang secara sah dapat menyelesaikan masalah sistem.**

Dalam DANUM actor tersebut adalah `Super Admin`.

Tetapi:

> **Full access tidak berarti tanpa audit, tanpa policy, atau tanpa batas keamanan.**

Super Admin adalah safety net sistem, bukan bypass terhadap governance.
