# Rencana DANUM Integration API

## Status

Dokumen ini adalah **rencana pengembangan ke depan**. Belum merupakan implementasi fitur API eksternal.

DANUM saat ini sudah memiliki API pada `routes/api.php`, tetapi API tersebut belum diformalkan sebagai platform integrasi eksternal yang berdiri sendiri.

## Kondisi Saat Ini

API yang sudah tersedia antara lain:

- Tenant
- User
- Letter Type dan version
- Permission Letter Type
- Outgoing Letter
- Workflow surat: submit, validate, reject, issue, sign, cancel
- Preview surat
- PDF surat
- History surat
- Withdrawal surat
- Tenant profile
- Position dan holder
- Public verification

API yang ada saat ini mayoritas menggunakan middleware `auth` dan belum menggunakan lapisan API integration khusus dengan token, scope, versioning, dan lifecycle client eksternal.

## Arah Pengembangan

DANUM nantinya dapat menyediakan **DANUM Integration API v1** untuk memungkinkan aplikasi lain berintegrasi secara resmi dengan DANUM.

Arsitektur yang direncanakan:

```text
Aplikasi Eksternal
       |
       | HTTPS + API Token
       v
DANUM Integration API v1
       |
       +-- API Client
       +-- Tenant Isolation
       +-- Scopes / Permissions
       +-- Rate Limiting
       +-- Audit Log
       +-- Validation
       v
DANUM Core
```

## Prinsip Utama

1. API eksternal dipisahkan dari API internal yang sudah ada.
2. Gunakan versioning, misalnya `/api/v1/...`.
3. Setiap aplikasi eksternal memiliki API Client sendiri.
4. Setiap client terikat dengan tenant yang jelas.
5. Access token memiliki scope/permission yang terbatas.
6. Tenant isolation tetap wajib berlaku pada setiap request.
7. API tidak boleh memberikan akses lebih besar daripada permission client.
8. Request penting harus dapat diaudit.
9. Endpoint yang berisi data sensitif harus dibuat sangat terbatas.
10. Jangan membuka endpoint internal hanya dengan menambahkan token tanpa audit authorization.

## Modul yang Direncanakan

### 1. API Client Management

Admin dapat mengelola aplikasi yang terhubung:

- Nama aplikasi
- Deskripsi
- Tenant
- Status aktif/nonaktif
- Token/credential
- Scope
- Waktu dibuat
- Waktu terakhir digunakan
- Revoke credential

### 2. Authentication

Gunakan mekanisme token/API credential yang sesuai untuk machine-to-machine integration.

Token tidak boleh disimpan dalam bentuk plaintext jika tidak diperlukan. Credential yang sudah diterbitkan harus dapat direvoke dan diganti.

### 3. API Scopes

Contoh scope yang dapat dirancang:

```text
letters.read
letters.create
letters.status
letters.document
letters.history
letter-types.read
verification.read
population.read
```

Scope harus mengikuti prinsip least privilege.

### 4. Letter API

Ini menjadi kandidat prioritas pertama karena proses persuratan merupakan inti DANUM.

Contoh rancangan endpoint:

```text
POST /api/v1/letters
GET  /api/v1/letters/{id}
GET  /api/v1/letters/{id}/status
GET  /api/v1/letters/{id}/document
GET  /api/v1/letters/{id}/history
```

Contoh alur integrasi:

```text
Aplikasi Eksternal
        |
        | create letter
        v
DANUM
        |
        +-- validate client
        +-- validate tenant
        +-- validate letter type
        +-- validate variables
        +-- create letter
        v
DRAFT / workflow DANUM
```

Aplikasi eksternal kemudian dapat mengambil status surat tanpa harus mengetahui detail implementasi internal DANUM.

### 5. Letter Status API

Aplikasi eksternal dapat mengetahui status surat secara aman, misalnya:

```text
DRAFT
SUBMITTED
VALIDATED
ISSUED
SIGNED
REJECTED
CANCELLED
```

Response API harus menggunakan format JSON yang konsisten dan tidak membocorkan data internal yang tidak diperlukan.

### 6. Verification API

Public verification yang sudah ada dapat dikembangkan lebih lanjut, tetapi harus tetap dibedakan dari API integrasi yang membutuhkan credential.

Endpoint verifikasi publik harus mendapat hardening seperti rate limiting dan optimasi terhadap proses verifikasi yang mahal.

### 7. Webhook

Tahap lanjutan yang direncanakan adalah webhook agar aplikasi eksternal tidak perlu melakukan polling terus-menerus.

Contoh event:

```text
letter.created
letter.submitted
letter.validated
letter.issued
letter.signed
letter.rejected
letter.cancelled
```

Contoh konsep:

```text
DANUM
  |
  | POST webhook
  v
Aplikasi Eksternal
```

Webhook harus memiliki mekanisme keamanan, retry, signature verification, dan idempotency.

### 8. Population API

API kependudukan harus diperlakukan lebih ketat daripada API surat karena data yang diberikan dapat bersifat sensitif.

Jangan langsung membuka endpoint seperti:

```text
GET /api/v1/citizens
```

tanpa kebutuhan dan authorization yang jelas.

Lebih baik dimulai dari endpoint yang sangat spesifik terhadap kebutuhan layanan dan scope khusus, misalnya:

```text
population.read
```

Setiap endpoint harus menentukan data minimum yang benar-benar diperlukan oleh aplikasi pemanggil.

## Pembagian Endpoint

Sebelum implementasi, seluruh endpoint yang ada perlu dipetakan menjadi empat kategori:

### READY

Sudah memiliki fondasi authorization dan cocok dijadikan dasar API eksternal setelah versioning dan authentication formal.

### REFACTOR

Sudah memiliki fungsi yang dibutuhkan tetapi perlu pemisahan service/controller, response contract, authorization, atau validasi sebelum diekspos.

### RESTRICTED

Boleh tersedia untuk integrasi tertentu tetapi membutuhkan scope khusus dan pembatasan ketat.

### DO NOT EXPOSE

Endpoint internal/admin atau operasi sensitif yang tidak boleh diberikan kepada aplikasi eksternal.

## Security Requirements

Sebelum API eksternal dianggap production-ready, minimal harus tersedia:

- HTTPS
- API authentication
- API client lifecycle
- Token rotation/revocation
- Scope/permission
- Tenant isolation
- Rate limiting
- Request validation
- Consistent error response
- Audit logging
- Idempotency untuk operasi tertentu
- Proteksi terhadap replay/duplicate request bila diperlukan
- Dokumentasi API

## API Response Contract

Response API perlu distandarkan.

Contoh sukses:

```json
{
    "data": {
        "id": "...",
        "status": "issued"
    }
}
```

Contoh error:

```json
{
    "message": "Validation failed",
    "errors": {
        "letter_type": [
            "The selected letter type is invalid."
        ]
    }
}
```

Format final harus ditetapkan sebelum banyak endpoint eksternal dibuat agar tidak terjadi perubahan kontrak yang sulit dipelihara.

## OpenAPI / Dokumentasi

Setelah kontrak API stabil, dokumentasi OpenAPI/Swagger perlu dibuat agar aplikasi lain dapat mengintegrasikan DANUM tanpa membaca source code DANUM.

Dokumentasi minimal harus menjelaskan:

- Base URL
- Authentication
- Scopes
- Endpoint
- Parameter
- Request body
- Response
- Error
- HTTP status code
- Rate limit
- Webhook/event
- Contoh request dan response

## Urutan Implementasi yang Disarankan

```text
1. Audit API/controller/policy yang sudah ada
          |
2. Tentukan resource dan recovery/security boundary
          |
3. Tetapkan API contract
          |
4. API authentication + client management
          |
5. Scope + tenant isolation
          |
6. /api/v1/letters
          |
7. status + document + history
          |
8. rate limiting + audit
          |
9. webhook
          |
10. population API secara selektif
          |
11. OpenAPI documentation
```

## Catatan Penting

API internal yang sudah ada **tidak perlu langsung dihapus atau diganti**. API Integration v1 sebaiknya dibangun sebagai lapisan yang terkontrol di atas domain/service DANUM yang sudah ada.

Tujuannya adalah mencegah duplikasi business logic dan menjaga agar workflow resmi DANUM tetap menjadi sumber kebenaran.

Dokumen ini menjadi **backlog/arah pengembangan**, bukan instruksi untuk langsung membuka API DANUM ke internet.
