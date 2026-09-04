<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'tenant_id', 'name', 'slug', 'scope', 'is_system', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function resolveSystemForTenant(string $slug, string|int $tenantId): ?self
    {
        return static::query()
            ->where('slug', $slug)
            ->where('is_system', true)
            ->where('is_active', true)
            ->where(function ($query) use ($tenantId) {
                $query->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 ELSE 1 END', [$tenantId])
            ->first();
    }

    public static function findActiveForTenant(string|int $id, string|int $tenantId): ?self
    {
        return static::query()
            ->whereKey($id)
            ->where('is_active', true)
            ->where(function ($query) use ($tenantId) {
                $query->where(fn ($q) => $q->where('scope', 'global')->whereNull('tenant_id'))
                    ->orWhere(fn ($q) => $q->where('scope', 'tenant')->where('tenant_id', $tenantId))
                    ->orWhere(fn ($q) => $q->where('scope', 'tenant')->whereNull('tenant_id')->where('is_system', true));
            })
            ->first();
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'custom_role_id');
    }
}
