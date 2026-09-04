<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;

class PositionManagementService
{
    public function categoryIdForUser(User $user): string
    {
        return (string) Tenant::query()->whereKey($user->tenant_id)->value('tenant_category_id');
    }

    public function find(string $id): Position
    {
        return Position::query()->findOrFail($id);
    }

    public function resetCategory(User $user): string
    {
        return $user->tenant_id ? $this->categoryIdForUser($user) : '';
    }

    public function activeHolder(Position $position, PositionService $positions)
    {
        $holder = $positions->getActiveHolder($position);
        $holder?->loadMissing('user');

        return $holder;
    }

    public function activeUsers(User $user, mixed $categoryId): Collection
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
}
