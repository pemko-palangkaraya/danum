# DANUM — BSrE Integration Readiness

## Baseline

DANUM already has PDF generation, individual signer accounts, audit logs, status history, visible TTE/verification QR, PAdES B-T/TSA plumbing, document hashing, and public signature verification.

## Application-side requirements implemented in this phase

- Signing uses a passphrase input with minimum 8 characters.
- Passphrase input disables browser autocomplete and spell/check assistance.
- Passphrase is not stored in the user model, database, session, cache, or audit payload.
- Successful signing continues to generate an audit event.
- Failed signing records an `outgoing_letter.sign_failed` audit event and server log.
- Login displays the BSrE logo from the configurable official asset URL.
- Final PDF footer is configurable and defaults to the BSrE-BSSN wording required by the integration guideline.

## External prerequisite

The official BSrE guideline defines the eSign Client as a BSrE service installed on the institution's Development and Production server. The public guideline does not define the exact endpoint contract, so DANUM must not invent values. For the Palangka Raya development service, the supplied working implementation establishes the following integration shape: HTTPS eSign Client service, Basic Auth for the application, `POST /api/sign/pdf`, multipart PDF upload, and signer fields including `nik` and `passphrase`, plus visible-signature/QR placement fields.

Before enabling production BSrE signing:

1. Install/configure the BSrE eSign Client supplied by BSrE.
2. Obtain the Development/Production endpoint and integration credentials/parameters from BSrE.
3. Configure `TTE_URL`, `TTE_USERNAME`, `TTE_PASSWORD`, and the signing endpoint/TLS settings.
4. Connect the signing workflow to the supplied eSign Client contract, including NIK and visible-signature parameters.
5. Run the BSrE integration test and document verification test.
6. Only then enable `BSRE_ESIGN_ENABLED=true` in the target environment.

Until the supplied eSign Client contract is integrated, the existing local signing implementation remains available for development/testing and must not be represented as BSrE-issued signing.

## Reference

Pedoman Kriteria Integrasi Sistem BSrE, versi 1.0, 1 August 2024.
