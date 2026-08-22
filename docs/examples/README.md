# DANUM DOCX template samples

Use `surat-keterangan-domisili-template.docx` as a reference when testing Letter Type templates.

Reserved markers:
- `{{letterhead}}` — tenant-uploaded letterhead
- `{{number}}` — generated letter number
- `{{date}}` — user-selected letter date
- `{{tenant_name}}`, `{{tenant_city}}`, `{{tenant_village}}` — tenant data
- `{{tenant_head_name}}`, `{{tenant_head_title}}` — tenant leadership data
- `{{tte}}` — verification QR/TTE anchor

All other placeholders are Letter Type input variables. The DOCX controls the layout; the application must not inject fixed fields such as Tujuan/Alamat/Perihal outside the DOCX.