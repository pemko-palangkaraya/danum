<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PositionStatus;
use App\Enums\PositionType;
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
        'tenant_category_id', 'code', 'name', 'description', 'position_type', 'parent_id', 'sort_order', 'status', 'can_sign', 'can_validate',
    ];

    protected function casts(): array
    {
        return [
            'status' => PositionStatus::class,
            'position_type' => PositionType::class,
            'can_sign' => 'boolean',
            'can_validate' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(TenantCategory::class, 'tenant_category_id'); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name'); }
    public function holders(): HasMany { return $this->hasMany(PositionHolder::class); }

    public function signerCertificates(): HasMany
    {
        $relation = $this->hasMany(SignerCertificate::class);
        $user = auth()->user();

        if ($user && ! $user->isSuperAdmin() && $user->tenant_id) {
            $relation->whereHas('user', fn ($query) => $query->where('tenant_id', $user->tenant_id));
        }

        return $relation;
    }

    public function activeSignerCertificate(): ?SignerCertificate
    {
        return $this->signerCertificates()->where('is_active', true)->latest('created_at')->first();
    }

    public function isManagerial(): bool
    {
        return $this->position_type === PositionType::MANAGERIAL;
    }

    public function allowsMultipleActiveHolders(): bool
    {
        return $this->position_type?->allowsMultipleActiveHolders() ?? false;
    }
}
