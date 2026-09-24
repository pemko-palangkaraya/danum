<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

final class SignerPassphraseService
{
    public const MIN_LENGTH = 8;
    public const MAX_LENGTH = 255;

    /**
     * Validate the passphrase without ever persisting it.
     *
     * The actual passphrase must be forwarded only to the configured
     * BSrE eSign Client during the signing request.
     */
    public function validate(string $passphrase): void
    {
        $passphrase = trim($passphrase);

        if ($passphrase === '') {
            throw new DomainException('Passphrase penanda tangan wajib diisi.');
        }

        if (mb_strlen($passphrase) < self::MIN_LENGTH) {
            throw new DomainException('Passphrase penanda tangan minimal 8 karakter.');
        }

        if (mb_strlen($passphrase) > self::MAX_LENGTH) {
            throw new DomainException('Passphrase penanda tangan terlalu panjang.');
        }
    }
}
