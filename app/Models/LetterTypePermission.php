<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterTypePermission extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'tenant_category_id',
        'letter_type_id',
        'allowed',
    ];

    protected function casts(): array
    {
        return [
            'allowed' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TenantCategory::class, 'tenant_category_id');
    }
}
