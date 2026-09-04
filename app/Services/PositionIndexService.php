<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;
use App\Models\SignerCertificate;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PositionIndexService
{
    public function categoryIdFor(User $user, ?string $selectedCategoryId = null): mixed
    {
        return $user->tenant_id
            ? Tenant::query()->whereKey($user->tenant_id)->value('tenant_category_id')
            : ($selectedCategoryId ?: null);
    }

    public function positions(User $user, mixed $categoryId, string $search, string $filter, int $perPage): LengthAwarePaginator
    {
        $query = Position::query()
            ->with([
                'category',
                'holders.user',
                'signerCertificates' => fn ($q) => $q->where('is_active', true)->latest('created_at'),
            ])
            ->orderBy('name');

        if ($categoryId) {
            $query->where('tenant_category_id', $categoryId);
        } elseif (! $user->isSuperAdmin()) {
            $query->whereRaw('1 = 0');
        }

        if ($search !== '') {
            $query->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        if ($filter === 'deleted') {
            $query->onlyTrashed();
        } elseif ($filter !== 'all') {
            $query->where('status', $filter);
        }

        return $query->paginate($perPage);
    }

    public function holderUsers(User $user, mixed $categoryId): Collection
    {
        if ($user->tenant_id) {
            return User::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'email']);
        }

        if (! $categoryId) {
            return collect();
        }

        return User::query()
            ->whereIn('tenant_id', Tenant::query()->where('tenant_category_id', $categoryId)->pluck('id'))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function history(?string $positionId, User $user): Collection
    {
        if (! $positionId) {
            return collect();
        }

        $position = Position::withTrashed()->find($positionId);

        if (! $position) {
            return collect();
        }

        return $position->holders()
            ->with('user')
            ->when($user->tenant_id, fn ($q) => $q->where('tenant_id', $user->tenant_id))
            ->orderByDesc('started_at')
            ->get();
    }

    public function certificate(?string $positionId): ?SignerCertificate
    {
        if (! $positionId) {
            return null;
        }

        return Position::query()
            ->find($positionId)
            ?->signerCertificates()
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }

    public function categories(): Collection
    {
        return TenantCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
    }

    public function categoryTenants(mixed $categoryId): Collection
    {
        return $categoryId
            ? Tenant::query()->where('tenant_category_id', $categoryId)->orderBy('name')->get(['id', 'name'])
            : collect();
    }

    public function preparePositions(LengthAwarePaginator $positions, User $user): void
    {
        foreach ($positions->getCollection() as $position) {
            $position->setRelation('tenant', $position->category);

            if ($user->tenant_id) {
                $position->setRelation(
                    'holders',
                    $position->holders->where('tenant_id', $user->tenant_id)->values(),
                );
            }
        }
    }
}
