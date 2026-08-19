<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutgoingLetterStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingLetterStatusHistory extends Model
{
    use HasFactory;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'outgoing_letter_id',
        'changed_by',
        'status',
        'action',
    ];

    protected function casts(): array
    {
        return ['status' => OutgoingLetterStatus::class];
    }

    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
