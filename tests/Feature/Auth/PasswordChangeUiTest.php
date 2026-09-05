<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Settings\Password;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordChangeUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_sees_success_toast_after_password_change(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        Livewire::actingAs($user)
            ->test(Password::class)
            ->set('currentPassword', 'old-password')
            ->set('newPassword', 'new-password')
            ->set('newPasswordConfirmation', 'new-password')
            ->call('save')
            ->assertDispatched('toast', type: 'success', message: 'Password berhasil diubah.')
            ->assertHasNoErrors();

        $storedPassword = $user->fresh()->password;
        $this->assertFalse(Hash::check('old-password', $storedPassword));
        $this->assertTrue(Hash::check('new-password', $storedPassword));
    }

    public function test_authenticated_user_sees_error_toast_when_current_password_is_wrong(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        Livewire::actingAs($user)
            ->test(Password::class)
            ->set('currentPassword', 'wrong-password')
            ->set('newPassword', 'new-password')
            ->set('newPasswordConfirmation', 'new-password')
            ->call('save')
            ->assertHasErrors(['currentPassword'])
            ->assertDispatched('toast', type: 'error', message: 'Password gagal diubah. Periksa password saat ini.');

        $storedPassword = $user->fresh()->password;
        $this->assertTrue(Hash::check('old-password', $storedPassword));
        $this->assertFalse(Hash::check('new-password', $storedPassword));
    }
}
