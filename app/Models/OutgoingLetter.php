<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutgoingLetterStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OutgoingLetter extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'tenant_id',
        'letter_type_id',
        'letter_type_version_id',
        'number',
        'recipient_name',
        'recipient_address',
        'subject',
        'content',
        'issued_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'status' => OutgoingLetterStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function letterTypeVersion(): BelongsTo
    {
        return $this->belongsTo(LetterTypeVersion::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OutgoingLetterStatusHistory::class)
            ->orderBy('created_at');
    }
}
