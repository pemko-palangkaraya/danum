<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        {{ $title ?? 'DANUM' }}
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <x-ui.toast />
    @livewireStyles
</head>

<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">

    <div class="min-h-screen">

        @include('layouts.components.mobile-header')

        <div class="flex min-h-screen">

            @include('layouts.components.sidebar')

            <div class="min-w-0 flex-1">

                @include('layouts.components.topbar')

                <main>
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>

            </div>

        </div>

    </div>

    @livewireScripts

</body>

</html>