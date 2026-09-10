<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LetterFont;
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
        'tenant_id', 'letter_classification_id', 'code', 'name', 'description', 'body_template', 'template_path', 'variables', 'status', 'font_family',
        'has_expiry', 'validity_days', 'validity_period', 'deletion_scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LetterTypeStatus::class,
            'font_family' => LetterFont::class,
            'variables' => 'array',
            'has_expiry' => 'boolean',
            'validity_days' => 'integer',
            'deletion_scheduled_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function classification(): BelongsTo { return $this->belongsTo(LetterClassification::class, 'letter_classification_id'); }
    public function versions(): HasMany { return $this->hasMany(LetterTypeVersion::class)->orderByDesc('version'); }
    public function permissions(): HasMany { return $this->hasMany(LetterTypePermission::class); }
    public function currentVersion(): ?LetterTypeVersion { return $this->versions()->first(); }
    public function isGlobal(): bool { return $this->tenant_id === null; }

    public function hasValidityPeriod(): bool
    {
        return ($this->validity_period ?? 'none') !== 'none';
    }

    public function deletionIsScheduled(): bool
    {
        return $this->deletion_scheduled_at !== null && ! $this->trashed();
    }
}
