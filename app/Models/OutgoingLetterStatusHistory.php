<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetterWithdrawalRequest;
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
        'note',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $history): void {
            if ($history->note !== null || $history->outgoing_letter_id === null) {
                return;
            }

            $letter = OutgoingLetter::query()->find($history->outgoing_letter_id);
            if (! $letter) {
                return;
            }

            $history->note = match ($history->action) {
                'validated' => $letter->verification_note,
                'issued' => $letter->signing_note,
                'rejected' => $letter->rejection_reason,
                'withdrawal_requested' => OutgoingLetterWithdrawalRequest::query()
                    ->where('outgoing_letter_id', $letter->id)
                    ->latest('requested_at')
                    ->value('reason'),
                'withdrawn', 'withdrawal_rejected' => OutgoingLetterWithdrawalRequest::query()
                    ->where('outgoing_letter_id', $letter->id)
                    ->whereNotNull('decision_note')
                    ->latest('decided_at')
                    ->value('decision_note'),
                default => null,
            };
        });
    }

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
