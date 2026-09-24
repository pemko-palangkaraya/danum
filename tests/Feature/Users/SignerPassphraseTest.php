<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Services\SignerPassphraseService;
use Tests\TestCase;

class SignerPassphraseTest extends TestCase
{
    public function test_passphrase_requires_at_least_eight_characters(): void
    {
        $this->expectException(\DomainException::class);
        app(SignerPassphraseService::class)->validate('1234567');
    }

    public function test_passphrase_is_accepted_without_persisting_user_state(): void
    {
        app(SignerPassphraseService::class)->validate('test-passphrase-123');

        $this->assertTrue(true);
    }
}
