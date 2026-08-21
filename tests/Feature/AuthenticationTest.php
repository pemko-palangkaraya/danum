<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_visit_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertSuccessful();
    }

    public function test_login_component_can_authenticate_user_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@danum.test',
            'password' => 'password',
        ]);

        Livewire::test('pages.auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_component_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@danum.test',
            'password' => 'password',
        ]);

        Livewire::test('pages.auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('dashboard'));

        $response->assertSuccessful();
        $response->assertSee('Dashboard');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guest_can_visit_register_page(): void
    {
        $response = $this->get(route('register'));

        $response->assertSuccessful();
    }

    public function test_user_can_register_with_valid_data(): void
    {
        Livewire::test('pages.auth.register')
            ->set('name', 'DANUM User')
            ->set('email', 'user@danum.test')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertRedirectToRoute('dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'DANUM User',
            'email' => 'user@danum.test',
        ]);

        $this->assertAuthenticated();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'existing@danum.test',
        ]);

        Livewire::test('pages.auth.register')
            ->set('name', 'Another User')
            ->set('email', 'existing@danum.test')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertHasErrors([
                'email' => 'unique',
            ]);

        $this->assertGuest();
    }

    public function test_registration_requires_password_confirmation(): void
    {
        Livewire::test('pages.auth.register')
            ->set('name', 'DANUM User')
            ->set('email', 'user@danum.test')
            ->set('password', 'password')
            ->set('password_confirmation', 'different-password')
            ->call('register')
            ->assertHasErrors([
                'password' => 'confirmed',
            ]);

        $this->assertGuest();
    }

    public function test_guest_can_visit_forgot_password_page(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertSuccessful();
    }

    public function test_user_can_request_password_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'user@danum.test',
        ]);

        Livewire::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_password_reset_request_requires_valid_email(): void
    {
        Livewire::test('pages.auth.forgot-password')
            ->set('email', 'invalid-email')
            ->call('sendPasswordResetLink')
            ->assertHasErrors([
                'email' => 'email',
            ]);
    }

    public function test_password_reset_request_rejects_unknown_email(): void
    {
        Notification::fake();

        Livewire::test('pages.auth.forgot-password')
            ->set('email', 'unknown@danum.test')
            ->call('sendPasswordResetLink')
            ->assertHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_guest_can_visit_reset_password_page(): void
    {
        $user = User::factory()->create([
            'email' => 'user@danum.test',
        ]);

        $token = Password::createToken($user);

        $response = $this->get(
            route('password.reset', ['token' => $token])
                . '?email=' . urlencode($user->email)
        );

        $response->assertSuccessful();
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@danum.test',
            'password' => 'old-password',
        ]);

        $token = Password::createToken($user);

        Livewire::test('pages.auth.reset-password', [
            'token' => $token,
            'email' => $user->email,
        ])
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('resetPassword')
            ->assertRedirectToRoute('login');

        $user->refresh();

        $this->assertTrue(
            Hash::check('new-password', $user->password)
        );

        $this->assertFalse(
            Hash::check('old-password', $user->password)
        );
    }

    public function test_reset_password_requires_matching_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'user@danum.test',
        ]);

        $token = Password::createToken($user);

        Livewire::test('pages.auth.reset-password', [
            'token' => $token,
            'email' => $user->email,
        ])
            ->set('password', 'new-password')
            ->set('password_confirmation', 'different-password')
            ->call('resetPassword')
            ->assertHasErrors([
                'password' => 'confirmed',
            ]);
    }

    public function test_invalid_reset_password_token_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'user@danum.test',
        ]);

        Livewire::test('pages.auth.reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
        ])
            ->set('password', 'new-password')
            ->set('password_confirmation', 'new-password')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $user->refresh();

        $this->assertTrue(
            Hash::check('password', $user->password)
        );
    }
}
