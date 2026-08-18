<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Password;
use function Livewire\Volt\{state};

state([
    'name' => '',
    'email' => '',
    'password' => '',
    'password_confirmation' => '',
]);

$register = function (): void {
    $validated = $this->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => $validated['password'],
    ]);

    Auth::login($user);

    Session::regenerate();

    $this->redirect(route('dashboard'));
};

?>

<div>
    <form wire:submit="register">
        <div>
            <label for="name">Name</label>

            <input
                id="name"
                type="text"
                wire:model="name"
                autocomplete="name"
                required
            >

            @error('name')
                <span>{{ $message }}</span>
            @enderror
        </div>

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
                autocomplete="new-password"
                required
            >

            @error('password')
                <span>{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="password_confirmation">
                Confirm Password
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
            Register
        </button>
    </form>
</div>