<?php

declare(strict_types=1);

namespace AppModels;

use IlluminateDatabaseEloquentFactoriesHasFactory;
use IlluminateDatabaseEloquentModel;
use IlluminateDatabaseEloquentRelationsHasMany;

class TenantCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
