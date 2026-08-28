<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission(Permission::TENANTS_VIEW); }
    public function view(User $user, Tenant $tenant): bool { return $user->hasPermission(Permission::TENANTS_VIEW) && ($user->isSuperAdmin() || $user->tenant_id === $tenant->id); }
    public function create(User $user): bool { return $user->hasPermission(Permission::TENANTS_CREATE); }
    public function update(User $user, Tenant $tenant): bool { return $user->hasPermission(Permission::TENANTS_UPDATE); }
    public function delete(User $user, Tenant $tenant): bool { return $user->hasPermission(Permission::TENANTS_DELETE); }
    public function restore(User $user, Tenant $tenant): bool { return $user->hasPermission(Permission::TENANTS_UPDATE); }
    public function forceDelete(User $user, Tenant $tenant): bool { return $user->hasPermission(Permission::TENANTS_DELETE); }
    public function viewProfile(User $user, Tenant $tenant): bool { return $user->hasPermission(Permission::TENANT_PROFILE_VIEW) && ($user->isSuperAdmin() || $user->tenant_id === $tenant->id); }
    public function updateProfile(User $user, Tenant $tenant): bool { return $user->hasPermission(Permission::TENANT_PROFILE_UPDATE) && ($user->isSuperAdmin() || $user->tenant_id === $tenant->id); }
}
