<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignerCertificate extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'position_id', 'user_id', 'issuing_ca_id', 'type', 'serial_number', 'fingerprint_sha256',
        'certificate_pem', 'private_key_encrypted', 'valid_from', 'valid_until',
        'revoked_at', 'is_active', 'generated_by', 'metadata',
    ];

    protected $hidden = ['private_key_encrypted'];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'revoked_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function position(): BelongsTo { return $this->belongsTo(Position::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function generator(): BelongsTo { return $this->belongsTo(User::class, 'generated_by'); }
    public function issuingCa(): BelongsTo { return $this->belongsTo(CertificateAuthority::class, 'issuing_ca_id'); }

    public function isUsable(): bool
    {
        $serial = strtoupper(trim((string) $this->serial_number));
        $hasValidSerial = $serial !== '' && preg_match('/^[0]+$/', $serial) !== 1;

        return $hasValidSerial
            && $this->is_active
            && $this->revoked_at === null
            && $this->valid_from->lte(now())
            && $this->valid_until->gt(now());
    }
}
