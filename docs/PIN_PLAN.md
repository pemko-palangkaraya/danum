# Signer PIN

Signer PIN is a per-user signing authorization factor. It belongs in the **Jabatan** action menu because signing authority is configured from a position holder, while the credential itself belongs to the individual signer user.

UI action: **Kelola PIN Tanda Tangan**.

Rules:
- 6 numeric digits.
- Store only a one-way hash.
- Never expose or log the PIN.
- Five consecutive failures trigger a 15-minute lock.
- Successful verification resets the failure counter.
- The PIN is required immediately before PDF signing, after workflow note confirmation.
