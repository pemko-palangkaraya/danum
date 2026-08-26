<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\SignerCertificate;
use App\Models\User;
use App\Services\SignerCertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignerCertificateTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_signer_can_receive_a_public_certificate(): void
    {
        $generator = User::factory()->create();
        $signer = User::factory()->create(['name' => 'Budi Penandatangan']);
        $position = Position::factory()->create(['can_sign' => true]);
        $holder = PositionHolder::factory()->create(['position_id' => $position->id, 'user_id' => $signer->id, 'started_at' => now()->subDay(), 'ended_at' => null]);

        $certificate = app(SignerCertificateService::class)->generate($position->load('tenant'), $holder->load('user'), $generator);

        $this->assertInstanceOf(SignerCertificate::class, $certificate);
        $this->assertTrue($certificate->is_active);
        $this->assertSame($signer->id, $certificate->user_id);
        $this->assertNotEmpty($certificate->certificate_pem);
        $this->assertNotEmpty($certificate->private_key_encrypted);
        $this->assertNotEmpty($certificate->fingerprint_sha256);
        $this->assertTrue(openssl_x509_parse($certificate->certificate_pem) !== false);
        $this->assertTrue($certificate->valid_until->isAfter($certificate->valid_from));
    }

    public function test_regenerating_a_certificate_deactivates_the_previous_certificate(): void
    {
        $generator = User::factory()->create();
        $signer = User::factory()->create();
        $position = Position::factory()->create(['can_sign' => true]);
        $holder = PositionHolder::factory()->create(['position_id' => $position->id, 'user_id' => $signer->id, 'started_at' => now()->subDay(), 'ended_at' => null]);
        $service = app(SignerCertificateService::class);

        $first = $service->generate($position->load('tenant'), $holder->load('user'), $generator);
        $second = $service->generate($position->load('tenant'), $holder->load('user'), $generator);

        $this->assertFalse($first->refresh()->is_active);
        $this->assertNotNull($first->revoked_at);
        $this->assertTrue($second->is_active);
        $this->assertSame(1, SignerCertificate::query()->where('position_id', $position->id)->where('is_active', true)->count());
    }

    public function test_certificate_cannot_be_generated_for_a_non_signing_position(): void
    {
        $generator = User::factory()->create();
        $signer = User::factory()->create();
        $position = Position::factory()->create(['can_sign' => false]);
        $holder = PositionHolder::factory()->create(['position_id' => $position->id, 'user_id' => $signer->id, 'started_at' => now()->subDay(), 'ended_at' => null]);

        $this->expectException(\RuntimeException::class);
        app(SignerCertificateService::class)->generate($position->load('tenant'), $holder->load('user'), $generator);
    }
}
