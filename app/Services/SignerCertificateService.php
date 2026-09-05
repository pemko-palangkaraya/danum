<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;
use App\Models\PositionHolder;
use App\Models\SignerCertificate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SignerCertificateService
{
    public function __construct(private readonly CertificateAuthorityService $certificateAuthorities) {}

    public function generate(Position $position, PositionHolder $holder, User $generatedBy): SignerCertificate
    {
        if (! $position->can_sign) throw new RuntimeException('Jabatan ini belum diizinkan sebagai penandatangan.');
        if ($holder->position_id !== $position->id || $holder->ended_at !== null || $holder->started_at?->isFuture()) throw new RuntimeException('Pemegang jabatan aktif tidak valid.');
        if (! $holder->user) throw new RuntimeException('User penanda tangan tidak ditemukan.');

        $user = $holder->user;
        $tenantName = (string) ($holder->tenant?->name ?? 'DANUM');
        $commonName = trim($user->name) !== '' ? $user->name : (string) $user->email;

        $issued = $this->certificateAuthorities->issueSignerCertificate([
            'countryName' => 'ID',
            'stateOrProvinceName' => 'Kalimantan Tengah',
            'localityName' => $tenantName,
            'organizationName' => $tenantName,
            'organizationalUnitName' => $position->name,
            'commonName' => $commonName,
            'emailAddress' => (string) $user->email,
        ]);

        return DB::transaction(function () use ($position, $holder, $generatedBy, $issued): SignerCertificate {
            SignerCertificate::query()
                ->where('position_id', $position->id)
                ->where('user_id', $holder->user_id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'revoked_at' => now()]);

            $certificate = SignerCertificate::query()->create([
                'position_id' => $position->id,
                'user_id' => $holder->user_id,
                'issuing_ca_id' => $issued['issuing_ca']->id,
                'serial_number' => $issued['serial'],
                'certificate_pem' => $issued['certificate_pem'],
                'private_key_encrypted' => Crypt::encryptString($issued['private_key_pem']),
                'fingerprint_sha256' => $issued['fingerprint'],
                'valid_from' => $issued['valid_from'],
                'valid_until' => $issued['valid_until'],
                'is_active' => true,
                'generated_by' => $generatedBy->id,
                'metadata' => $issued['metadata'],
            ]);

            app(AuditLogService::class)->record('signer_certificate.generated', $generatedBy, $certificate, null, [
                'position_id' => $position->id,
                'user_id' => $holder->user_id,
                'issuing_ca_id' => $issued['issuing_ca']->id,
                'serial_number' => $issued['serial'],
                'fingerprint_sha256' => $issued['fingerprint'],
                'valid_from' => $issued['valid_from']->toIso8601String(),
                'valid_until' => $issued['valid_until']->toIso8601String(),
            ]);

            return $certificate;
        });
    }
}
