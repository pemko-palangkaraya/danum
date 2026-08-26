<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Models\User;
use App\Services\SignerPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SignerPinTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_receive_a_signing_pin(): void
    {
        $user = User::factory()->create();

        app(SignerPinService::class)->set($user, '123456');
        $user->refresh();

        $this->assertTrue(app(SignerPinService::class)->hasPin($user));
        $this->assertTrue(Hash::check('123456', $user->signing_pin_hash));
        $this->assertFalse(Hash::check('654321', $user->signing_pin_hash));
        $this->assertNotNull($user->signing_pin_set_at);
    }

    public function test_invalid_pin_format_is_rejected(): void
    {
        $this->expectException(\DomainException::class);
        app(SignerPinService::class)->set(User::factory()->create(), '12345');
    }

    public function test_wrong_pin_locks_after_five_attempts(): void
    {
        $user = User::factory()->create();
        $service = app(SignerPinService::class);
        $service->set($user, '123456');

        for ($i = 0; $i < 5; $i++) {
            try {
                $service->verify($user->refresh(), '000000');
            } catch (\DomainException) {
                // expected
            }
        }

        $user->refresh();
        $this->assertTrue($user->signing_pin_locked_until?->isFuture() ?? false);

        $this->expectException(\DomainException::class);
        $service->verify($user, '123456');
    }

    public function test_correct_pin_resets_failed_attempts(): void
    {
        $user = User::factory()->create();
        $service = app(SignerPinService::class);
        $service->set($user, '123456');

        try {
            $service->verify($user, '000000');
        } catch (\DomainException) {
        }

        $service->verify($user->refresh(), '123456');
        $user->refresh();

        $this->assertSame(0, $user->signing_pin_failed_attempts);
        $this->assertNull($user->signing_pin_locked_until);
    }
}
