<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_succeeds(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $data = [
            'name' => 'New User',
            'nip' => '123456789',
            'email' => 'new-user@test.local',
            'password' => 'password123',
            'role' => UserRole::TENANT_USER->value,
            'tenant_id' => $tenant->id,
        ];

        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required'],
            'tenant_id' => ['required', 'uuid', 'exists:tenants,id'],
        ])->validate();

        $this->assertTrue($admin->can('create', User::class));

        $validated['password'] = Hash::make($validated['password']);
        $user = app(UserService::class)->create($validated);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New User',
            'email' => 'new-user@test.local',
            'tenant_id' => $tenant->id,
            'role' => UserRole::TENANT_USER->value,
        ]);
    }

    public function test_update_user_without_changing_email_succeeds(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->tenantUser($tenant)->create([
            'email' => 'unchanged@test.local',
        ]);

        $validated = Validator::make([
            'name' => 'Updated Name',
            'email' => 'unchanged@test.local',
            'role' => UserRole::TENANT_USER->value,
            'tenant_id' => $tenant->id,
        ], UpdateUserRequest::rulesFor($user))->validate();

        $this->assertTrue($admin->can('update', $user));

        app(UserService::class)->update($user, $validated);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'unchanged@test.local',
        ]);
    }

    public function test_update_user_to_new_email_succeeds(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create([
            'email' => 'old-email@test.local',
        ]);

        $validated = Validator::make([
            'name' => $user->name,
            'email' => 'new-email@test.local',
            'role' => UserRole::TENANT_USER->value,
            'tenant_id' => $tenant->id,
        ], UpdateUserRequest::rulesFor($user))->validate();

        app(UserService::class)->update($user, $validated);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new-email@test.local',
        ]);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
            'email' => 'old-email@test.local',
        ]);
    }

    public function test_update_user_to_another_users_email_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->tenantUser($tenant)->create([
            'email' => 'first@test.local',
        ]);
        User::factory()->tenantUser($tenant)->create([
            'email' => 'taken@test.local',
        ]);

        $this->expectException(ValidationException::class);

        Validator::make([
            'email' => 'taken@test.local',
        ], UpdateUserRequest::rulesFor($user))->validate();
    }

    public function test_tenant_admin_cannot_manage_user_from_another_tenant(): void
    {
        $ownTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $tenantAdmin = User::factory()->tenantAdmin($ownTenant)->create();
        $otherUser = User::factory()->tenantUser($otherTenant)->create();

        $this->assertFalse($tenantAdmin->can('view', $otherUser));
        $this->assertFalse($tenantAdmin->can('update', $otherUser));
    }

    public function test_super_admin_has_user_management_access_according_to_policy(): void
    {
        $tenant = Tenant::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $target = User::factory()->tenantUser($tenant)->create();

        $this->assertTrue($superAdmin->can('viewAny', User::class));
        $this->assertTrue($superAdmin->can('view', $target));
        $this->assertTrue($superAdmin->can('create', User::class));
        $this->assertTrue($superAdmin->can('update', $target));
        $this->assertTrue($superAdmin->can('delete', $target));
    }
}
