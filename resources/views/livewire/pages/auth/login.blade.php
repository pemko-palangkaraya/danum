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
        $this->addError('email', 'Email atau password yang Anda masukkan salah.');

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

    <div class="mb-7 text-center">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Masuk ke DANUM</h1>
        <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">Gunakan akun Anda untuk melanjutkan.</p>
    </div>

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="email"
                autofocus
                class="block h-11 w-full rounded-xl border border-gray-300 bg-white px-3.5 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                placeholder="nama@contoh.go.id">
            @error('email')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between gap-3">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <a href="{{ route('password.request') }}" class="shrink-0 text-xs font-medium text-yellow-600 hover:text-yellow-500 dark:text-yellow-400">Lupa password?</a>
            </div>

            <div x-data="{ showPassword: false }" class="relative">
                <input
                    wire:model="password"
                    id="password"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    autocomplete="current-password"
                    class="block h-11 w-full rounded-xl border border-gray-300 bg-white px-3.5 pr-12 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    placeholder="Masukkan password">

                <button
                    type="button"
                    x-on:click="showPassword = !showPassword"
                    x-bind:aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-xl text-gray-500 transition hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-yellow-400 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.644C3.423 7.51 7.36 5 12 5c4.64 0 8.577 2.51 9.964 6.678.07.21.07.434 0 .644C20.577 16.49 16.64 19 12 19c-4.64 0-8.577-2.51-9.964-6.678ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.584 10.587a2 2 0 0 0 2.829 2.829M9.88 5.09A9.953 9.953 0 0 1 12 5c4.64 0 8.577 2.51 9.964 6.678a1.012 1.012 0 0 1 0 .644 10.035 10.035 0 0 1-4.132 5.27M6.228 6.228A10.052 10.052 0 0 0 2.036 11.678a1.012 1.012 0 0 0 0 .644C3.423 16.49 7.36 19 12 19c1.04 0 2.052-.15 3-.43" />
                    </svg>
                </button>
            </div>

            @error('password')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="captchaAnswer" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Verifikasi keamanan</label>
            <div class="grid grid-cols-[minmax(0,1fr)_120px] gap-2.5">
                <div class="flex h-11 items-center justify-center rounded-xl border border-gray-300 bg-gray-50 px-3 text-sm font-semibold tracking-wide text-gray-800 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                    {{ $captchaQuestion }}
                </div>
                <input
                    wire:model="captchaAnswer"
                    id="captchaAnswer"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    class="block h-11 w-full rounded-xl border border-gray-300 bg-white px-3 text-center text-sm font-medium text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
                    placeholder="Jawaban">
            </div>
            <p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">Masukkan hasil perhitungan di atas.</p>
            @error('captchaAnswer')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <label for="remember" class="flex cursor-pointer items-center gap-2.5">
            <input wire:model="remember" id="remember" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-yellow-500 focus:ring-2 focus:ring-yellow-400/30 dark:border-gray-600 dark:bg-gray-800">
            <span class="text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
        </label>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:loading.attr="aria-busy"
            wire:target="login"
            class="flex h-11 w-full items-center justify-center rounded-xl bg-yellow-400 px-4 text-sm font-semibold text-gray-950 shadow-sm transition hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-gray-900">
            <span wire:loading.remove wire:target="login">Masuk</span>
            <span wire:loading wire:target="login">Memproses...</span>
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-medium text-yellow-600 hover:text-yellow-500 dark:text-yellow-400">Daftar</a>
    </p>
</div>
