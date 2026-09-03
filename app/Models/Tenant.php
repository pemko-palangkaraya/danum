<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code', 'name', 'tenant_category_id', 'province', 'city', 'district', 'village', 'address',
        'phone', 'email', 'logo', 'letterhead_path', 'head_name', 'head_title',
        'status', 'administrator_user_id',
    ];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['status' => TenantStatus::class];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TenantCategory::class, 'tenant_category_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'administrator_user_id');
    }

    public function positionStructures(): HasMany
    {
        return $this->hasMany(TenantPositionStructure::class);
    }

    public function letterheadUrl(): ?string
    {
        return $this->letterhead_path
            ? asset('storage/' . $this->letterhead_path)
            : null;
    }
}
