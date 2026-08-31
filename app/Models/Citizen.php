<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Citizen extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'tenant_id', 'nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir',
        'jenis_kelamin', 'golongan_darah', 'agama', 'status_perkawinan',
        'pendidikan', 'pekerjaan', 'kewarganegaraan', 'no_passport', 'no_kitap',
        'nama_ayah', 'nik_ayah', 'nama_ibu', 'nik_ibu', 'status_kependudukan',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function familyMemberships(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CitizenAddress::class);
    }

    public function populationEvents(): HasMany
    {
        return $this->hasMany(PopulationEvent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
