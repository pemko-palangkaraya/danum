<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userService = $this->app->make(UserService::class);
    }

    public function test_it_can_find_a_user(): void
    {
        $user = User::factory()->create();

        $result = $this->userService->find($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($user->id, $result->id);
    }

    public function test_it_can_find_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'service@example.com',
        ]);

        $result = $this->userService->findByEmail('service@example.com');

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($user->id, $result->id);
    }

    public function test_it_can_get_all_users(): void
    {
        User::factory()->count(3)->create();

        $result = $this->userService->getAll();

        $this->assertCount(3, $result);
    }

    public function test_it_can_create_a_user(): void
    {
        $tenant = Tenant::factory()->create();

        $user = $this->userService->create([
            'name' => 'Service User',
            'email' => 'service-create@example.com',
            'password' => 'password',
            'role' => UserRole::TENANT_USER,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Service User', $user->name);
        $this->assertSame(UserRole::TENANT_USER, $user->role);
        $this->assertSame($tenant->id, $user->tenant_id);
    }

    public function test_it_can_update_a_user(): void
    {
        $user = User::factory()->create();

        $updated = $this->userService->update($user, [
            'name' => 'Updated Service User',
        ]);

        $this->assertSame('Updated Service User', $updated->name);
    }

    public function test_it_can_delete_a_user(): void
    {
        $user = User::factory()->create();

        $result = $this->userService->delete($user);

        $this->assertTrue($result);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_it_resolves_user_repository_from_the_container(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);

        $this->assertInstanceOf(
            UserRepositoryInterface::class,
            $repository,
        );
    }
}