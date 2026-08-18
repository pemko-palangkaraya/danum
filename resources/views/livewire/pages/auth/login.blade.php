<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use function Livewire\Volt\{state};

state([
    'email' => '',
    'password' => '',
    'remember' => false,
]);

$login = function (): void {
    $this->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt([
        'email' => $this->email,
        'password' => $this->password,
    ], $this->remember)) {
        $this->addError('email', 'The provided credentials are incorrect.');

        return;
    }

    Session::regenerate();

    $this->redirectIntended(route('dashboard'));
};

?>

<div>
    <form wire:submit="login">
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
            <label for="password">Password</label>

            <input
                id="password"
                type="password"
                wire:model="password"
                autocomplete="current-password"
                required
            >

            @error('password')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label>
                <input
                    type="checkbox"
                    wire:model="remember"
                >

                Remember me
            </label>
        </div>

        <button type="submit">
            Login
        </button>
    </form>
</div>