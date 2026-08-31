<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case DASHBOARD_VIEW = 'dashboard.view'; case RBAC_VIEW = 'rbac.view'; case RBAC_MANAGE = 'rbac.manage'; case USERS_VIEW = 'users.view'; case USERS_CREATE = 'users.create'; case USERS_UPDATE = 'users.update'; case USERS_DELETE = 'users.delete'; case TENANT_USERS_VIEW = 'tenant-users.view'; case TENANT_PROFILE_VIEW = 'tenant-profile.view'; case TENANT_PROFILE_UPDATE = 'tenant-profile.update'; case TENANTS_VIEW = 'tenants.view'; case TENANTS_CREATE = 'tenants.create'; case TENANTS_UPDATE = 'tenants.update'; case TENANTS_DELETE = 'tenants.delete'; case POSITIONS_VIEW = 'positions.view'; case POSITIONS_MANAGE = 'positions.manage'; case LETTER_TYPES_VIEW = 'letter-types.view'; case LETTER_TYPES_MANAGE = 'letter-types.manage'; case LETTER_TYPES_PERMISSIONS = 'letter-types.permissions'; case LETTER_TYPES_VERSIONS = 'letter-types.versions'; case OUTGOING_LETTERS_VIEW = 'outgoing-letters.view'; case OUTGOING_LETTERS_CREATE = 'outgoing-letters.create'; case OUTGOING_LETTERS_UPDATE = 'outgoing-letters.update'; case OUTGOING_LETTERS_DELETE = 'outgoing-letters.delete'; case OUTGOING_LETTERS_SUBMIT = 'outgoing-letters.submit'; case OUTGOING_LETTERS_VALIDATE = 'outgoing-letters.validate'; case OUTGOING_LETTERS_REJECT = 'outgoing-letters.reject'; case OUTGOING_LETTERS_ISSUE = 'outgoing-letters.issue'; case OUTGOING_LETTERS_WITHDRAW = 'outgoing-letters.withdraw'; case AUDIT_LOGS_VIEW = 'audit-logs.view'; case POPULATION_VIEW = 'population.view'; case POPULATION_MANAGE = 'population.manage';

    public static function forCustomRole(): array
    {
        if (auth()->check() && auth()->user()?->isSuperAdmin()) return self::cases();
        return [self::DASHBOARD_VIEW, self::RBAC_VIEW, self::POSITIONS_VIEW, self::LETTER_TYPES_VIEW, self::OUTGOING_LETTERS_VIEW, self::OUTGOING_LETTERS_CREATE, self::OUTGOING_LETTERS_UPDATE, self::OUTGOING_LETTERS_DELETE, self::OUTGOING_LETTERS_SUBMIT, self::OUTGOING_LETTERS_VALIDATE, self::OUTGOING_LETTERS_REJECT, self::OUTGOING_LETTERS_ISSUE, self::OUTGOING_LETTERS_WITHDRAW, self::POPULATION_VIEW, self::POPULATION_MANAGE];
    }
}
