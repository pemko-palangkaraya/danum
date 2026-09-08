<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationLog extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'document_id',
        'user_id',
        'user_type',
        'action',
        'result',
        'classification',
        'access_method',
        'ip_address',
        'user_agent',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(OutgoingLetter::class, 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
