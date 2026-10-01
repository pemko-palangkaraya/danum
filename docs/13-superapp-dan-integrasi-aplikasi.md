# DANUM — SuperApp dan Arsitektur Integrasi Aplikasi

**Status:** Rencana arsitektur / target evolusi  
**Scope:** SuperApp Shell, SSO, child applications, API integration  
**Tanggal:** 1 Oktober 2026

## 1. Tujuan

DANUM dipersiapkan untuk berevolusi dari platform persuratan menjadi **SuperApp Shell** bagi ekosistem aplikasi Pemerintah Kota. Evolusi ini dilakukan tanpa mengubah setiap child application menjadi satu monolith.

Prinsip utama:

> DANUM menjadi pintu masuk dan pengalaman terpadu; setiap child application tetap memiliki domain, business logic, database, deployment, dan lifecycle sendiri.

Contoh child application:

- DANUM Persuratan;
- POSLAP Karhutla;
- PETAK;
- aplikasi OPD lainnya yang akan diintegrasikan kemudian.

## 2. Prinsip independensi child application

Child application **tidak di-merger secara kode atau database** ke dalam DANUM.

Setiap child application tetap dapat:

- memiliki repository sendiri;
- memiliki database sendiri;
- memiliki business logic sendiri;
- memiliki API sendiri;
- memiliki deployment sendiri;
- memiliki domain sendiri;
- dibuka langsung melalui domainnya sendiri.

Contoh:

```text
DANUM   -> danum.go.id
PETAK   -> petak.go.id
POSLAP  -> poslap.go.id
```

DANUM menyediakan application launcher dan pengalaman terpadu, bukan mengambil alih seluruh domain aplikasi child.

## 3. Konsep SuperApp Shell

DANUM menjadi shell/pintu masuk utama:

```text
                         DANUM SUPERAPP
                              |
             +----------------+----------------+
             |                |                |
             v                v                v
        Persuratan          PETAK           POSLAP
        Child App         Child App        Child App
```

Pengguna secara default berpindah antar aplikasi tanpa harus login ulang.

Jika user memilih PETAK dari DANUM, browser dapat berpindah dari `danum.go.id` ke `petak.go.id` pada tab yang sama. Membuka tab baru bukan perilaku default; tab baru merupakan pilihan pengguna/browser.

## 4. Single Sign-On

Ekosistem menggunakan **Identity Provider terpusat**, direncanakan menggunakan Keycloak.

```text
                         KEYCLOAK
                      Identity Provider
                             |
              +--------------+--------------+
              |              |              |
              v              v              v
            DANUM          PETAK          POSLAP
            Client         Client         Client
```

DANUM bukan Identity Provider untuk seluruh ekosistem. Keycloak menjadi infrastructure identitas bersama.

### Alur dari DANUM

```text
User
  -> DANUM
  -> Keycloak
  -> Login / SSO
  -> DANUM
  -> pilih PETAK
  -> petak.go.id
  -> PETAK memeriksa session/identity Keycloak
  -> masuk tanpa login ulang
```

### Alur langsung ke child application

```text
User
  -> petak.go.id
  -> PETAK
  -> Keycloak
  -> jika sudah authenticated: masuk otomatis
  -> jika belum: login
```

Dengan demikian PETAK tetap dapat digunakan secara mandiri maupun melalui DANUM.

## 5. Authentication vs Authorization

Authentication dan authorization harus dipisahkan.

**Authentication:**

> Siapa user ini?

Ditangani oleh Keycloak/Identity Provider.

**Authorization:**

> User ini boleh melakukan apa di aplikasi ini?

Tetap menjadi tanggung jawab masing-masing aplikasi.

Contoh:

```text
Keycloak
  -> identity = user-123

PETAK
  -> user-123
  -> role/permission PETAK

DANUM
  -> user-123
  -> role/permission DANUM
```

DANUM tidak perlu mengetahui seluruh permission internal PETAK.

## 6. Migrasi aplikasi yang sudah memiliki login sendiri

Aplikasi seperti PETAK yang sudah memiliki authentication sendiri tidak perlu langsung dibangun ulang.

Tahap integrasi:

```text
Tahap 1
PETAK
  +-- Login lokal
  +-- Keycloak / SSO

Tahap 2
PETAK
  +-- Keycloak / SSO sebagai jalur utama
  +-- migrasi identity mapping

Tahap 3 (jika diputuskan)
PETAK
  +-- Keycloak / SSO
  +-- login lokal dihentikan
```

PETAK perlu menyediakan integration boundary untuk OIDC/OAuth dan identity mapping. DANUM tidak menyimpan password PETAK dan tidak melakukan login ke PETAK menggunakan credential PETAK.

## 7. API Gateway

Kong digunakan sebagai API Gateway pada integration layer.

```text
PWA / SuperApp Shell
        |
        v
       BFF
        |
        v
      KONG
        |
   +----+---------+-----------+
   |              |           |
   v              v           v
DANUM API      PETAK API   POSLAP API
```

Kong bertanggung jawab terhadap concern gateway seperti routing, authentication enforcement sesuai desain, rate limiting, logging/observability, dan kebijakan API lainnya.

**Kong bukan adapter data.**

## 8. BFF

BFF (Backend for Frontend) menjadi backend yang melayani kebutuhan SuperApp UI.

```text
PWA
 |
 v
BFF
 |
 +--> DANUM API
 +--> PETAK API
 +--> POSLAP API
```

BFF tidak menjadi tempat business logic domain child application. Business rule tetap berada pada service/domain masing-masing aplikasi.

## 9. Integration Hub

Untuk integrasi lintas aplikasi dan layanan eksternal, disiapkan konsep **Integration Hub**.

Integration Hub menangani orkestrasi proses yang memang melibatkan lebih dari satu sistem.

```text
                 Integration Hub
                        |
          +-------------+-------------+
          |             |             |
          v             v             v
       Adapter A     Adapter B     Adapter C
          |             |             |
          v             v             v
       System A      System B      System C
```

Integration Hub tidak mengambil alih business domain child application.

## 10. Adapter Layer

Adapter adalah lapisan penerjemah antara kontrak sistem eksternal/child dengan kontrak integrasi yang digunakan oleh platform.

Adapter dapat menangani:

- perbedaan nama field;
- perbedaan struktur payload;
- perbedaan format tanggal/enum/status;
- perbedaan protokol atau kontrak API;
- mapping identifier;
- transformasi request/response.

Contoh:

```text
Sistem A
  latitude / longitude / frp
          |
          v
     Adapter A
          |
          v
Canonical Integration Model
          |
          v
     Adapter B
          |
          v
Sistem B
  lat / lng / fire_energy
```

**Adapter bukan BFF, bukan Kong, dan bukan Keycloak.**

## 11. Canonical Data Model

Jika beberapa sistem harus bertukar data yang sama, integration layer dapat menggunakan canonical model sebagai kontrak internal.

Contoh:

```json
{
  "location": {
    "latitude": -2.123,
    "longitude": 113.456
  },
  "detection": {
    "confidence": 87,
    "energy": 12.4,
    "source": "VIIRS"
  }
}
```

Canonical model tidak berarti seluruh domain child harus dipindahkan ke DANUM. Child application tetap menjadi source of truth untuk domainnya sendiri.

## 12. Source of Truth dan batas domain

Setiap child application tetap menjadi source of truth untuk domainnya.

Contoh:

```text
DANUM       -> persuratan
POSLAP      -> hotspot dan laporan Karhutla
PETAK       -> domain PETAK
```

DANUM tidak boleh mengakses database child secara langsung untuk transaksi normal.

Transaksi dilakukan melalui API/contract yang disediakan child application.

```text
DANUM
  -> BFF
  -> Kong
  -> Child API
  -> Child Service
  -> Child Database
```

Tidak diperbolehkan sebagai pola integrasi normal:

```text
DANUM
  -> langsung membaca/menulis database PETAK
```

## 13. Application Registry

DANUM perlu memiliki konsep **Application Registry** untuk mengetahui aplikasi yang tersedia dalam ekosistem.

Informasi konseptual dapat mencakup:

```text
Application
- code
- name
- slug
- base_url
- status
- icon/metadata
- launch configuration
```

Registry digunakan untuk application launcher, navigasi, status aplikasi, dan konfigurasi integrasi. Detail implementasi dan schema ditetapkan pada tahap desain tersendiri.

## 14. Pola akses browser

### Default

Pengguna tetap berada dalam satu tab browser dan berpindah antar aplikasi melalui navigasi SuperApp.

Contoh:

```text
danum.go.id
      |
      +--> pilih PETAK
              |
              v
         petak.go.id
```

Perpindahan domain tidak berarti user harus login ulang karena authentication menggunakan SSO.

### New tab

Membuka child application pada tab baru diperbolehkan jika pengguna secara eksplisit memilihnya, misalnya melalui browser action atau fitur `open in new tab`.

New tab bukan mekanisme utama integrasi.

## 15. Posisi komponen

```text
                         +----------------------+
                         |      KEYCLOAK        |
                         |   Identity / SSO     |
                         +----------+-----------+
                                    |
                    +---------------+---------------+
                    |               |               |
                    v               v               v
              +-----------+   +-----------+   +-----------+
              |   DANUM   |   |   PETAK   |   |  POSLAP   |
              | SuperApp  |   | Child App |   | Child App |
              +-----+-----+   +-----+-----+   +-----+-----+
                    |               |               |
                    +---------------+---------------+
                                    |
                                  APIs
                                    |
                                  KONG
                                    |
                           Integration Layer
                                    |
                    +---------------+---------------+
                    |               |               |
                    v               v               v
                 Adapter         Adapter         Adapter
```

## 16. Prinsip arsitektur yang dikunci untuk rencana evolusi

1. DANUM dapat berevolusi menjadi SuperApp Shell.
2. Child application tetap independen dan tidak menjadi satu monolith dengan DANUM.
3. Child application tetap dapat diakses melalui domainnya sendiri.
4. SSO menggunakan Identity Provider bersama; target teknologi adalah Keycloak.
5. Authentication dipusatkan; authorization tetap berada pada aplikasi masing-masing.
6. Kong berfungsi sebagai API Gateway, bukan adapter.
7. BFF melayani kebutuhan frontend SuperApp dan bukan pengganti domain service child.
8. Integration Hub menangani orkestrasi lintas sistem.
9. Adapter menangani translasi kontrak/data antar sistem.
10. Canonical Data Model digunakan bila diperlukan untuk pertukaran data lintas sistem.
11. DANUM tidak menyimpan password child application.
12. DANUM tidak mengakses database child secara langsung untuk transaksi normal.
13. Source of truth tetap berada pada pemilik domain masing-masing.
14. Same-tab menjadi default UX; new-tab adalah pilihan pengguna.
15. Integrasi dilakukan evolutif dan tidak membatalkan fondasi DANUM yang sudah ada.

## 17. Status implementasi

Dokumen ini merupakan **rencana arsitektur evolusi**, bukan instruksi untuk langsung mengimplementasikan seluruh komponen.

Urutan implementasi harus ditetapkan kemudian melalui roadmap dan architecture decision record (ADR) yang relevan.

Sebelum implementasi production, minimal perlu ditetapkan:

- Identity Provider dan realm/client strategy;
- OIDC flow dan session/logout strategy;
- identity mapping antar aplikasi;
- API contract;
- Kong deployment dan policy;
- BFF boundary;
- Integration Hub boundary;
- adapter contract;
- canonical data model yang benar-benar diperlukan;
- application registry;
- audit dan observability;
- security model;
- migration plan untuk aplikasi legacy yang sudah memiliki authentication sendiri.
