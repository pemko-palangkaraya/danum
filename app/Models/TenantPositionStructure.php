<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPositionStructure extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['tenant_id', 'position_id', 'parent_position_id', 'sort_order', 'is_root'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_root' => 'boolean'];
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function position(): BelongsTo { return $this->belongsTo(Position::class); }
    public function parentPosition(): BelongsTo { return $this->belongsTo(Position::class, 'parent_position_id'); }
}
