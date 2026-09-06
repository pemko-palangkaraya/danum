<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LetterVariableDefinition extends Model
{
    use HasUuids;

    protected $fillable = [
        'key', 'label', 'type', 'source', 'required', 'readonly', 'options',
        'description', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'readonly' => 'boolean',
            'options' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
