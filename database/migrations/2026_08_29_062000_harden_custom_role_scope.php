<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Repair custom roles created by older RBAC code as tenant-scoped roles
        // without a tenant. The creator's tenant is recoverable from the audit log.
        $orphanedRoles = DB::table('roles')
            ->where('is_system', false)
            ->where('scope', 'tenant')
            ->whereNull('tenant_id')
            ->get(['id', 'name']);

        foreach ($orphanedRoles as $role) {
            $tenantIds = DB::table('audit_logs as logs')
                ->join('users', 'users.id', '=', 'logs.user_id')
                ->where('logs.auditable_type', 'App\\Models\\Role')
                ->where('logs.auditable_id', (string) $role->id)
                ->where('logs.action', 'rbac.role.created')
                ->whereNotNull('users.tenant_id')
                ->distinct()
                ->pluck('users.tenant_id');

            if ($tenantIds->count() !== 1) {
                throw new RuntimeException(
                    "Custom role [{$role->name}] (ID {$role->id}) has scope=tenant without tenant_id and its owning tenant could not be determined from audit logs. Resolve this role manually before migrating."
                );
            }

            DB::table('roles')
                ->where('id', $role->id)
                ->update(['tenant_id' => $tenantIds->first(), 'updated_at' => now()]);
        }

        DB::statement(<<<'SQL'
            ALTER TABLE roles
            ADD CONSTRAINT roles_scope_tenant_consistency
            CHECK (
                is_system = true
                OR (scope = 'global' AND tenant_id IS NULL)
                OR (scope = 'tenant' AND tenant_id IS NOT NULL)
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE roles DROP CONSTRAINT IF EXISTS roles_scope_tenant_consistency');
    }
};
