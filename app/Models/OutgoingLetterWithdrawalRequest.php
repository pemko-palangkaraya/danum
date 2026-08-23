<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutgoingLetterWithdrawalStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutgoingLetterWithdrawalRequest extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'outgoing_letter_id',
        'requested_by',
        'requested_at',
        'reason',
        'statement_path',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => OutgoingLetterWithdrawalStatus::class,
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function outgoingLetter(): BelongsTo { return $this->belongsTo(OutgoingLetter::class); }
    public function requestedBy(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function decidedBy(): BelongsTo { return $this->belongsTo(User::class, 'decided_by'); }
}
