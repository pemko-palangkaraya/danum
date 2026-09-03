<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use function Livewire\Volt\{layout, mount, state};

layout('layouts.guest');

state([
    'email' => '',
    'password' => '',
    'remember' => false,
    'captchaAnswer' => '',
    'captchaQuestion' => '',
]);

mount(function (): void {
    $first = random_int(1, 9);
    $second = random_int(1, 9);

    $this->captchaQuestion = "{$first} + {$second} = ?";
    Session::put('login_captcha_answer', $first + $second);
});

$login = function (): void {
    $this->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'captchaAnswer' => ['required', 'integer'],
    ], [
        'captchaAnswer.required' => 'Jawaban captcha wajib diisi.',
        'captchaAnswer.integer' => 'Jawaban captcha harus berupa angka.',
    ]);

    $expectedCaptcha = Session::pull('login_captcha_answer');

    if ($expectedCaptcha === null || (int) $this->captchaAnswer !== (int) $expectedCaptcha) {
        $this->addError('captchaAnswer', 'Jawaban captcha salah. Silakan coba lagi.');

        $first = random_int(1, 9);
        $second = random_int(1, 9);
        $this->captchaQuestion = "{$first} + {$second} = ?";
        Session::put('login_captcha_answer', $first + $second);
        $this->captchaAnswer = '';

        return;
    }

    if (! Auth::attempt([
        'email' => $this->email,
        'password' => $this->password,
    ], $this->remember)) {
        $this->addError('email', 'The provided credentials are incorrect.');

        $first = random_int(1, 9);
        $second = random_int(1, 9);
        $this->captchaQuestion = "{$first} + {$second} = ?";
        Session::put('login_captcha_answer', $first + $second);
        $this->captchaAnswer = '';

        return;
    }

    Session::regenerate();

    $this->redirectIntended(route('dashboard'));
};

?>

<div>
    <div class="mb-7 flex justify-center sm:mb-8">
        <a
            href="{{ route('login') }}"
            class="inline-flex items-center justify-center text-3xl font-black tracking-[0.25em] text-yellow-400 transition-transform duration-200 hover:scale-[1.02]"
            aria-label="DANUM">
            DANUM
        </a>
    </div>

    <div class="mb-6 text-center">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Masuk ke DANUM</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gunakan akun Anda untuk melanjutkan.</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                autofocus
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-yellow-400 focus:ring-yellow-400 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                placeholder="nama@contoh.go.id">
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-yellow-600 hover:text-yellow-500 dark:text-yellow-400">Lupa password?</a>
            </div>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-yellow-400 focus:ring-yellow-400 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                placeholder="••••••••">
            @error('password')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="captchaAnswer" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Verifikasi keamanan</label>
            <div class="mt-1 flex gap-3">
                <div class="flex min-w-[130px] items-center justify-center rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-base font-semibold tracking-wide text-gray-800 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    {{ $captchaQuestion }}
                </div>
                <input
                    wire:model="captchaAnswer"
                    id="captchaAnswer"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    class="block min-w-0 flex-1 rounded-lg border-gray-300 shadow-sm focus:border-yellow-400 focus:ring-yellow-400 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    placeholder="Jawaban">
            </div>
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Masukkan hasil perhitungan di atas.</p>
            @error('captchaAnswer')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center">
            <input wire:model="remember" id="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-yellow-500 focus:ring-yellow-400 dark:border-gray-600 dark:bg-gray-800">
            <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-400">Ingat saya</label>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            class="flex w-full items-center justify-center rounded-lg bg-yellow-400 px-4 py-2.5 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
            <span wire:loading.remove>Login</span>
            <span wire:loading>Memproses...</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-medium text-yellow-600 hover:text-yellow-500 dark:text-yellow-400">Daftar</a>
    </p>
</div>
