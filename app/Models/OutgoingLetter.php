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
use Illuminate\Support\Str;

class OutgoingLetter extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id','letter_type_id','letter_type_version_id',
        'signer_position_id','signer_user_id','signer_name','signer_title',
        'validator_position_id','validator_user_id','validator_name','validator_title',
        'number','recipient_name','recipient_address','subject','content','issued_at','letter_date','generated_docx_path','status','verification_token',
    ];

    protected $hidden = ['verification_token'];

    protected static function booted(): void
    {
        static::saving(function (self $letter): void {
            if ($letter->status === OutgoingLetterStatus::ISSUED && blank($letter->verification_token)) $letter->verification_token = Str::random(64);
        });
    }

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'letter_date' => 'date', 'status' => OutgoingLetterStatus::class];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function letterType(): BelongsTo { return $this->belongsTo(LetterType::class); }
    public function letterTypeVersion(): BelongsTo { return $this->belongsTo(LetterTypeVersion::class); }
    public function signerPosition(): BelongsTo { return $this->belongsTo(Position::class, 'signer_position_id'); }
    public function signerUser(): BelongsTo { return $this->belongsTo(User::class, 'signer_user_id'); }
    public function validatorPosition(): BelongsTo { return $this->belongsTo(Position::class, 'validator_position_id'); }
    public function validatorUser(): BelongsTo { return $this->belongsTo(User::class, 'validator_user_id'); }
    public function statusHistories(): HasMany { return $this->hasMany(OutgoingLetterStatusHistory::class)->orderBy('created_at'); }
}
