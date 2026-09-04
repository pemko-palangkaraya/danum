<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;

class UserRoleAssignmentService
{
    public function normalize(array $data): array
    {
        $role = $data['role'] ?? null;
        $tenantId = $data['tenant_id'] ?? null;
        $customRoleId = $data['custom_role_id'] ?? null;

        if ($role === 'super_admin') {
            return array_merge($data, [
                'platform_role' => 'super_admin',
                'tenant_id' => null,
                'custom_role_id' => null,
            ]);
        }

        if ($tenantId !== null && $customRoleId === null && in_array($role, ['tenant_admin', 'tenant_user'], true)) {
            $systemRole = Role::resolveSystemForTenant($role, $tenantId);

            if ($systemRole !== null) {
                $data['platform_role'] = null;
                $data['custom_role_id'] = $systemRole->getKey();
            }
        }

        return $data;
    }
}
