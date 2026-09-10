<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class AuditLogQueryService
{
    private const SORTABLE_COLUMNS = [
        'created' => 'created_at',
        'actor' => 'user_id',
        'tenant' => 'tenant_id',
        'action' => 'action',
        'object' => 'auditable_type',
    ];

    public function paginate(array $filters = [], int $perPage = 5): LengthAwarePaginator
    {
        $sortBy = (string) ($filters['sortBy'] ?? 'created');
        $sortColumn = self::SORTABLE_COLUMNS[$sortBy] ?? self::SORTABLE_COLUMNS['created'];
        $sortDirection = ($filters['sortDirection'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return AuditLog::query()
            ->with([
                'user:id,name,email,tenant_id',
                'tenant:id,name,code',
            ])
            ->when($filters['actor'] ?? '', fn ($query, $value) => $query->where('user_id', $value))
            ->when($filters['tenant'] ?? '', fn ($query, $value) => $query->where('tenant_id', $value))
            ->when($filters['action'] ?? '', fn ($query, $value) => $query->where('action', $value))
            ->when($filters['object'] ?? '', function ($query, $value): void {
                $value = '%' . trim((string) $value) . '%';

                $query->where(function ($objectQuery) use ($value): void {
                    $objectQuery
                        ->where('auditable_type', 'like', $value)
                        ->orWhere('auditable_id', 'like', $value);
                });
            })
            ->when($filters['search'] ?? '', function ($query, $value): void {
                $value = '%' . trim((string) $value) . '%';

                $query->where(function ($searchQuery) use ($value): void {
                    $searchQuery
                        ->where('action', 'like', $value)
                        ->orWhere('auditable_type', 'like', $value)
                        ->orWhere('auditable_id', 'like', $value)
                        ->orWhere('ip_address', 'like', $value)
                        ->orWhereHas('user', function ($userQuery) use ($value): void {
                            $userQuery
                                ->where('name', 'like', $value)
                                ->orWhere('email', 'like', $value);
                        })
                        ->orWhereHas('tenant', function ($tenantQuery) use ($value): void {
                            $tenantQuery
                                ->where('name', 'like', $value)
                                ->orWhere('code', 'like', $value);
                        });
                });
            })
            ->when($filters['dateFrom'] ?? '', fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['dateTo'] ?? '', fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(max(5, min($perPage, 50)));
    }

    public function actors(): Collection
    {
        return User::query()->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function tenants(): Collection
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function actions(): Collection
    {
        return AuditLog::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');
    }
}
