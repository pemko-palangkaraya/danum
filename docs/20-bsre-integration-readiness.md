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

The official BSrE guideline defines the eSign Client as a BSrE service installed on the institution's Development and Production server. The exact eSign Client request/response contract is not published in the public guideline. Therefore DANUM must not invent an API contract.

Before enabling production BSrE signing:

1. Install/configure the BSrE eSign Client supplied by BSrE.
2. Obtain the Development/Production endpoint and integration credentials/parameters from BSrE.
3. Configure `BSRE_ESIGN_CLIENT_URL` and the TLS settings.
4. Connect the signing workflow to the supplied eSign Client contract.
5. Run the BSrE integration test and document verification test.
6. Only then enable `BSRE_ESIGN_ENABLED=true` in the target environment.

Until the supplied eSign Client contract is integrated, the existing local signing implementation remains available for development/testing and must not be represented as BSrE-issued signing.

## Reference

Pedoman Kriteria Integrasi Sistem BSrE, versi 1.0, 1 August 2024.
