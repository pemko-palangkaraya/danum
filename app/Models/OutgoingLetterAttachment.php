<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingLetterAttachment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'outgoing_letter_id',
        'sequence',
        'title',
        'source',
        'file_path',
        'original_name',
        'mime_type',
        'page_count',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'page_count' => 'integer',
            'file_size' => 'integer',
        ];
    }

    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }
}
