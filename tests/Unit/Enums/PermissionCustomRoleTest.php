<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Permission;
use PHPUnit\Framework\TestCase;

class PermissionCustomRoleTest extends TestCase
{
    public function test_custom_roles_only_expose_operational_permissions(): void
    {
        $slugs = array_map(
            fn (Permission $permission): string => $permission->value,
            Permission::forCustomRole(),
        );

        $this->assertContains('dashboard.view', $slugs);
        $this->assertContains('outgoing-letters.validate', $slugs);
        $this->assertContains('outgoing-letters.issue', $slugs);

        $this->assertNotContains('users.view', $slugs);
        $this->assertNotContains('users.create', $slugs);
        $this->assertNotContains('users.delete', $slugs);
        $this->assertNotContains('rbac.view', $slugs);
        $this->assertNotContains('rbac.manage', $slugs);
        $this->assertNotContains('tenants.create', $slugs);
        $this->assertNotContains('tenants.delete', $slugs);
        $this->assertNotContains('audit-logs.view', $slugs);
    }
}
