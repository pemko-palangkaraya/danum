<?php

namespace App\Models;

use App\Enums\LetterTypeStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterType extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'code', 'name', 'description', 'body_template', 'template_path', 'variables', 'status',
        'has_expiry', 'validity_days', 'validity_period',
    ];

    protected function casts(): array
    {
        return [
            'status' => LetterTypeStatus::class,
            'variables' => 'array',
            'has_expiry' => 'boolean',
            'validity_days' => 'integer',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function versions(): HasMany { return $this->hasMany(LetterTypeVersion::class)->orderByDesc('version'); }
    public function permissions(): HasMany { return $this->hasMany(LetterTypePermission::class); }
    public function currentVersion(): ?LetterTypeVersion { return $this->versions()->first(); }
    public function isGlobal(): bool { return $this->tenant_id === null; }

    public function hasValidityPeriod(): bool
    {
        return ($this->validity_period ?? 'none') !== 'none';
    }
}
