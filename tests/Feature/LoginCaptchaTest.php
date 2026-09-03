<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class LoginCaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_a_correct_math_captcha(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $component = Livewire::test('pages.auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->set('captchaAnswer', '999')
            ->call('login')
            ->assertHasErrors(['captchaAnswer']);

        $this->assertGuest();
        $this->assertSame('', $component->get('captchaAnswer'));
    }

    public function test_login_succeeds_with_a_correct_math_captcha(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        Livewire::test('pages.auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->set('captchaAnswer', function () {
                return (string) Session::get('login_captcha_answer');
            })
            ->call('login');

        $this->assertAuthenticatedAs($user);
        $this->assertNull(Session::get('login_captcha_answer'));
    }
}
