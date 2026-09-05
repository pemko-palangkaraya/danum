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
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OutgoingLetter extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id','created_by','citizen_id','letter_type_id','letter_type_version_id',
        'signer_position_id','signer_user_id','signer_name','signer_title',
        'validator_position_id','validator_user_id','validator_name','validator_title',
        'number','recipient_name','recipient_address','subject','content','input_data','issued_at','valid_from','valid_until','letter_date','generated_docx_path','unsigned_pdf_path','signed_pdf_path','signature_certificate_id','signature_profile','signed_at','status','submitted_at','verification_token',
        'rejection_reason','rejected_by','rejected_at','verification_note','signing_note',
    ];

    protected $hidden = ['verification_token'];

    protected static function booted(): void
    {
        static::saving(function (self $letter): void {
            if ($letter->status === OutgoingLetterStatus::ISSUED && blank($letter->verification_token)) {
                $letter->verification_token = Str::random(64);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'letter_date' => 'date',
            'submitted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'signed_at' => 'datetime',
            'input_data' => 'array',
            'status' => OutgoingLetterStatus::class,
        ];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function citizen(): BelongsTo { return $this->belongsTo(Citizen::class); }
    public function rejectedBy(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function letterType(): BelongsTo { return $this->belongsTo(LetterType::class); }
    public function letterTypeVersion(): BelongsTo { return $this->belongsTo(LetterTypeVersion::class); }
    public function signerPosition(): BelongsTo { return $this->belongsTo(Position::class, 'signer_position_id'); }
    public function signerUser(): BelongsTo { return $this->belongsTo(User::class, 'signer_user_id'); }
    public function signerCertificate(): BelongsTo { return $this->belongsTo(SignerCertificate::class, 'signature_certificate_id'); }
    public function validatorPosition(): BelongsTo { return $this->belongsTo(Position::class, 'validator_position_id'); }
    public function validatorUser(): BelongsTo { return $this->belongsTo(User::class, 'validator_user_id'); }
    public function statusHistories(): HasMany { return $this->hasMany(OutgoingLetterStatusHistory::class)->orderBy('created_at'); }
    public function withdrawalRequests(): HasMany { return $this->hasMany(OutgoingLetterWithdrawalRequest::class)->latest('created_at'); }

    private function localValidityTime(?Carbon $value): ?Carbon
    {
        return $value?->shiftTimezone(config('app.timezone'));
    }

    public function isExpired(): bool
    {
        $validUntil = $this->localValidityTime($this->valid_until);

        return $this->status === OutgoingLetterStatus::ISSUED
            && $validUntil !== null
            && $validUntil->isPast();
    }

    public function isActive(): bool
    {
        $validFrom = $this->localValidityTime($this->valid_from);

        return $this->status === OutgoingLetterStatus::ISSUED
            && ($validFrom === null || $validFrom->lessThanOrEqualTo(now()))
            && ! $this->isExpired();
    }
}
