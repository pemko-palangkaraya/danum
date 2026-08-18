<?php

use Illuminate\Support\Facades\Password;
use function Livewire\Volt\{state};

state([
    'email' => '',
]);

$sendPasswordResetLink = function (): void {
    $this->validate([
        'email' => ['required', 'email'],
    ]);

    $status = Password::sendResetLink([
        'email' => $this->email,
    ]);

    if ($status !== Password::RESET_LINK_SENT) {
        $this->addError('email', __($status));

        return;
    }

    session()->flash('status', __($status));
};

?>

<div>
    <h1>Forgot Password</h1>

    @if (session('status'))
        <div>
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="sendPasswordResetLink">
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

        <button type="submit">
            Send Password Reset Link
        </button>
    </form>
</div>