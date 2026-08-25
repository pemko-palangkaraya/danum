<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LetterTypeVersion extends Model
{
    protected $fillable = [
        'letter_type_id', 'version', 'body_template', 'template_path',
        'effective_from', 'effective_until', 'is_active', 'change_note', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function letterType(): BelongsTo { return $this->belongsTo(LetterType::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
