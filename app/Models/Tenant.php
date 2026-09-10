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
        'code', 'name', 'tenant_category_id', 'parent_tenant_id', 'province', 'city', 'district', 'village', 'address',
        'phone', 'email', 'logo', 'letterhead_path', 'letterhead_line1', 'letterhead_line2', 'letterhead_line3',
        'letterhead_line1_size', 'letterhead_line2_size', 'letterhead_line3_size', 'letterhead_meta_size',
        'postal_code', 'website', 'head_name', 'head_title', 'head_nip', 'status', 'administrator_user_id',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_tenant_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_tenant_id');
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

    public function logoUrl(): ?string
    {
        if (! $this->logo) return null;
        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://') || str_starts_with($this->logo, '/')) {
            return $this->logo;
        }

        return asset('storage/' . $this->logo);
    }
}
