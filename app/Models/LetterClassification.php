<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LetterClassification extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'name',
        'description',
        'source',
        'number_format',
        'number_padding',
        'sort_order',
        'source_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number_padding' => 'integer',
            'sort_order' => 'integer',
            'source_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function letterTypes(): HasMany
    {
        return $this->hasMany(LetterType::class);
    }

    public function numberSequences(): HasMany
    {
        return $this->hasMany(LetterNumberSequence::class);
    }
}
