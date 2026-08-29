<?php

declare(strict_types=1);

namespace App\Enums;

/** @deprecated Use PlatformRole and RBAC Role. */
enum UserRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case TENANT_ADMIN = 'tenant_admin';
    case TENANT_USER = 'tenant_user';
}
