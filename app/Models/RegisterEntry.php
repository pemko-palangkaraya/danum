<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RegisterEntrySource;
use App\Enums\RegisterEntryStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegisterEntry extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'outgoing_letter_id',
        'registered_by',
        'register_number',
        'register_year',
        'letter_number',
        'letter_date',
        'letter_type_name',
        'classification_code',
        'subject',
        'recipient_name',
        'recipient_address',
        'signer_name',
        'signer_title',
        'source',
        'status',
        'correction_reason',
    ];

    protected function casts(): array
    {
        return [
            'register_number' => 'integer',
            'register_year' => 'integer',
            'letter_date' => 'date',
            'source' => RegisterEntrySource::class,
            'status' => RegisterEntryStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function outgoingLetter(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
