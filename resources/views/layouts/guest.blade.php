<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        {{ $title ?? 'Login — DANUM' }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">

    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">

        {{-- Background decoration --}}
        <div
            class="pointer-events-none absolute -left-32 -top-32 h-72 w-72 rounded-full bg-yellow-200/30 blur-3xl sm:h-96 sm:w-96">
        </div>

        <div
            class="pointer-events-none absolute -bottom-32 -right-32 h-72 w-72 rounded-full bg-slate-200/70 blur-3xl sm:h-96 sm:w-96">
        </div>

        {{-- Content --}}
        <div class="relative z-10 w-full max-w-md">

            {{ $slot }}

        </div>

    </main>

    @livewireScripts

</body>

</html>