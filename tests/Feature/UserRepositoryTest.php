<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_repository_is_bound_to_the_container(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);

        $this->assertInstanceOf(
            UserRepositoryInterface::class,
            $repository,
        );
    }

    public function test_it_can_find_a_user(): void
    {
        $user = User::factory()->create();

        $repository = $this->app->make(UserRepositoryInterface::class);

        $result = $repository->find($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($user->id, $result->id);
    }

    public function test_it_returns_null_when_user_is_not_found(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);

        // $result = $repository->find('00000000-0000-0000-0000-000000000000');
        $result = $repository->find(9999999);

        $this->assertNull($result);
    }

    public function test_it_can_find_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        $repository = $this->app->make(UserRepositoryInterface::class);

        $result = $repository->findByEmail('user@example.com');

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($user->id, $result->id);
    }

    public function test_it_can_get_all_users(): void
    {
        User::factory()->count(3)->create();

        $repository = $this->app->make(UserRepositoryInterface::class);

        $result = $repository->getAll();

        $this->assertCount(3, $result);
    }

    public function test_it_can_create_a_user(): void
    {
        $tenant = Tenant::factory()->create();

        $repository = $this->app->make(UserRepositoryInterface::class);

        $user = $repository->create([
            'name' => 'Test User',
            'email' => 'create@example.com',
            'password' => 'password',
            'role' => UserRole::TENANT_USER,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('create@example.com', $user->email);
        $this->assertSame(UserRole::TENANT_USER, $user->role);
        $this->assertSame($tenant->id, $user->tenant_id);
    }

    public function test_it_can_update_a_user(): void
    {
        $user = User::factory()->create();

        $repository = $this->app->make(UserRepositoryInterface::class);

        $updated = $repository->update($user, [
            'name' => 'Updated User',
        ]);

        $this->assertSame('Updated User', $updated->name);
        $this->assertSame(
            'Updated User',
            $user->refresh()->name,
        );
    }

    public function test_it_can_delete_a_user(): void
    {
        $user = User::factory()->create();

        $repository = $this->app->make(UserRepositoryInterface::class);

        $result = $repository->delete($user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}