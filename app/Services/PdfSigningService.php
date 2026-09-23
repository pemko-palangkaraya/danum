<?php

namespace App\Services;

use App\Models\SignerCertificate;
use App\Support\AuditLogger;
use Carbon\CarbonInterface;
use DomainException;
use RuntimeException;

class PdfSigningService
{
    private const DEFAULT_REASON = 'Tanda tangan elektronik dokumen resmi DANUM.';

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function sign(
        string $sourcePdfPath,
        SignerCertificate $certificate,
        string $signerName,
        ?string $outputPath = null,
        ?string $reason = null,
        ?CarbonInterface $signingTime = null,
        ?string $passphrase = null,
    ): string {
        // ...