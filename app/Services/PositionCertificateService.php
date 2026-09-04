<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;
use App\Models\SignerCertificate;
use App\Models\User;
use RuntimeException;

class PositionCertificateService
{
    public function position(Position $position): Position
    {
        return $position->loadMissing('signerCertificates');
    }

    public function activeCertificate(Position $position): ?SignerCertificate
    {
        return $position->signerCertificates()
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }

    public function validateSigner(Position $position, PositionService $positions): object
    {
        if (! $position->can_sign) {
            throw new RuntimeException('Jabatan ini belum diizinkan untuk TTE.');
        }

        $holder = $positions->getActiveHolder($position);
        $holder?->loadMissing('user');

        if (! $holder?->user) {
            throw new RuntimeException('Tetapkan pejabat aktif terlebih dahulu.');
        }

        return $holder;
    }

    public function download(Position $position): array
    {
        $certificate = $this->activeCertificate($position);

        if (! $certificate) {
            abort(404);
        }

        return [
            'certificate' => $certificate,
            'filename' => 'sertifikat-' . str($position->code)->slug() . '-' . str($certificate->fingerprint_sha256)->substr(0, 12) . '.pem',
        ];
    }
}
