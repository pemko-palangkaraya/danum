<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypePermission;

final class LetterTypePermissionService
{
    public function isAllowedForTenant(LetterType $letterType, string $tenantId): bool
    {
        if ($letterType->tenant_id === $tenantId) {
            return true;
        }

        if (! $letterType->isGlobal()) {
            return false;
        }

        return $this->permissionQuery($letterType, $tenantId)->exists();
    }

    public function grantTenant(LetterType $letterType, string $tenantId): LetterTypePermission
    {
        $this->ensureGlobal($letterType, 'Only global letter types can be assigned to tenants.');

        return LetterTypePermission::query()->updateOrCreate(
            [
                'letter_type_id' => $letterType->id,
                'tenant_id' => $tenantId,
                'tenant_category_id' => null,
            ],
            ['allowed' => true],
        );
    }

    public function revokeTenant(LetterType $letterType, string $tenantId): bool
    {
        if (! $letterType->isGlobal()) {
            return false;
        }

        return LetterTypePermission::query()
            ->where('letter_type_id', $letterType->id)
            ->where('tenant_id', $tenantId)
            ->whereNull('tenant_category_id')
            ->update(['allowed' => false]) > 0;
    }

    public function grantCategory(LetterType $letterType, int $categoryId): LetterTypePermission
    {
        $this->ensureGlobal($letterType, 'Only global letter types can be assigned to tenant categories.');

        return LetterTypePermission::query()->updateOrCreate(
            [
                'letter_type_id' => $letterType->id,
                'tenant_category_id' => $categoryId,
                'tenant_id' => null,
            ],
            ['allowed' => true],
        );
    }

    public function revokeCategory(LetterType $letterType, int $categoryId): bool
    {
        if (! $letterType->isGlobal()) {
            return false;
        }

        return LetterTypePermission::query()
            ->where('letter_type_id', $letterType->id)
            ->where('tenant_category_id', $categoryId)
            ->whereNull('tenant_id')
            ->update(['allowed' => false]) > 0;
    }

    private function permissionQuery(LetterType $letterType, string $tenantId)
    {
        return LetterTypePermission::query()
            ->where('letter_type_id', $letterType->id)
            ->where('allowed', true)
            ->where(function ($query) use ($tenantId): void {
                $query
                    ->where('tenant_id', $tenantId)
                    ->orWhereHas('category.tenants', fn ($tenants) => $tenants->whereKey($tenantId));
            });
    }

    private function ensureGlobal(LetterType $letterType, string $message): void
    {
        if (! $letterType->isGlobal()) {
            throw new \InvalidArgumentException($message);
        }
    }
}
