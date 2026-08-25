<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        string $action,
        ?User $user = null,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $tenantId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'user_id' => $user?->id,
            'tenant_id' => $tenantId ?? $user?->tenant_id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }
}
