<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateAuthority extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'type', 'name', 'parent_id', 'serial_number', 'fingerprint_sha256',
        'certificate_pem', 'private_key_encrypted', 'valid_from', 'valid_until',
        'revoked_at', 'is_active', 'metadata',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function signerCertificates(): HasMany
    {
        return $this->hasMany(SignerCertificate::class, 'issuing_ca_id');
    }

    public function isUsable(): bool
    {
        $serial = strtoupper(trim((string) $this->serial_number));

        return $serial !== ''
            && preg_match('/^0+$/', $serial) !== 1
            && $this->is_active
            && $this->revoked_at === null
            && $this->valid_from->lte(now())
            && $this->valid_until->gt(now());
    }
}
