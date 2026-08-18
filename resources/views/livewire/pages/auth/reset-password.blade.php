<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password as PasswordRule;
use function Livewire\Volt\{state};

state([
    'token' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
]);

$resetPassword = function (): void {
    $this->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'confirmed', PasswordRule::defaults()],
    ]);

    $status = Password::reset(
        [
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'token' => $this->token,
        ],
        function ($user, $password): void {
            $user->forceFill([
                'password' => $password,
            ])->save();

            Session::regenerate();

            $this->redirect(route('login'));
        }
    );

    if ($status !== Password::PASSWORD_RESET) {
        $this->addError('email', __($status));
    }
};

?>

<div>
    <h1>Reset Password</h1>

    <form wire:submit="resetPassword">
        <div>
            <label for="email">Email</label>

            <input
                id="email"
                type="email"
                wire:model="email"
                autocomplete="email"
                required
            >

            @error('email')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="password">New Password</label>

            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="new-password"
                required
            >

            @error('password')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="password_confirmation">
                Confirm New Password
            </label>

            <input
                id="password_confirmation"
                type="password"
                wire:model="password_confirmation"
                autocomplete="new-password"
                required
            >

            @error('password_confirmation')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <button type="submit">
            Reset Password
        </button>
    </form>
</div>