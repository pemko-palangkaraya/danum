<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\UserPasswordService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserPasswordServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $this->actingAs($user);

        app(UserPasswordService::class)->change($user, 'old-password', 'new-password');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $this->actingAs($user);

        $this->expectException(ValidationException::class);
        app(UserPasswordService::class)->change($user, 'wrong-password', 'new-password');
    }

    public function test_authorized_actor_can_reset_another_users_password(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('old-password')]);
        $this->actingAs($admin);

        app(UserPasswordService::class)->reset($user, 'new-password');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }
}
