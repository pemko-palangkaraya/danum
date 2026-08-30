<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PositionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_category_id', 'code', 'name', 'description', 'status', 'can_sign', 'can_validate',
    ];

    protected function casts(): array
    {
        return [
            'status' => PositionStatus::class,
            'can_sign' => 'boolean',
            'can_validate' => 'boolean',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(TenantCategory::class, 'tenant_category_id'); }
    public function holders(): HasMany { return $this->hasMany(PositionHolder::class); }
    public function signerCertificates(): HasMany { return $this->hasMany(SignerCertificate::class); }

    public function activeSignerCertificate(): ?SignerCertificate
    {
        return $this->signerCertificates()->where('is_active', true)->latest('created_at')->first();
    }
}
