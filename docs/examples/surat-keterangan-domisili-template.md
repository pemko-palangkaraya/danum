# Sampel Template Surat Keterangan Domisili

Template DOCX yang dipakai untuk pengujian DANUM sebaiknya memiliki placeholder berikut.

## Reserved/system markers

- `{{letterhead}}` — posisi kop surat dari data Tenant.
- `{{number}}` — nomor surat.
- `{{date}}` — tanggal surat yang dipilih Tenant User (hari ini tidak diperbolehkan).
- `{{tenant_name}}` — nama organisasi Tenant.
- `{{tenant_city}}` — kota Tenant.
- `{{tenant_village}}` — kelurahan/desa Tenant.
- `{{tenant_head_name}}` — nama kepala organisasi.
- `{{tenant_head_title}}` — jabatan kepala organisasi.
- `{{tte}}` — posisi QR verifikasi/TTE.

## Tenant input variables

- `{{recipient_name}}`
- `{{recipient_nik}}`
- `{{recipient_birth_place}}`
- `{{recipient_birth_date}}`
- `{{recipient_address}}`
- `{{subject}}`

## Suggested DOCX layout

Place `{{letterhead}}` at the very top of the document. The application replaces it with the Tenant's uploaded letterhead.

---

**SURAT KETERANGAN DOMISILI**

Nomor: `{{number}}`

Yang bertanda tangan di bawah ini menerangkan bahwa:

Nama                 : `{{recipient_name}}`
Tempat/Tanggal Lahir : `{{recipient_birth_place}}`, `{{recipient_birth_date}}`
NIK                  : `{{recipient_nik}}`
Alamat               : `{{recipient_address}}`
Keperluan            : `{{subject}}`

Adalah benar berdomisili di wilayah administrasi `{{tenant_village}}`, `{{tenant_city}}` berdasarkan data yang ada pada instansi kami.

Surat keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.

`{{tenant_city}}`, `{{date}}`

Pejabat yang berwenang,

`{{tenant_head_name}}`

`{{tte}}`

`{{tenant_head_title}}`

---

### Important

Do not add `Tujuan`, `Alamat`, `Perihal`, or any other fixed field outside the DOCX when building the outgoing letter. If a future letter type needs additional fields, add its placeholders to the DOCX and register those variables in the Letter Type. The DOCX is the source of truth for layout.