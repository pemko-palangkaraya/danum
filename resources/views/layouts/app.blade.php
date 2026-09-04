<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'DANUM' }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-ui.toast />
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen">
        @include('layouts.components.mobile-header')
        <div class="min-h-screen lg:flex">
            <div class="lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:w-64">
                @include('layouts.components.sidebar')
            </div>
            <div class="min-w-0 flex-1 lg:ml-64 lg:h-screen lg:overflow-y-auto lg:pt-20">
                @include('layouts.components.topbar')
                <main>
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </div>

    @include('layouts.components.workflow-note-modal')
    @include('layouts.components.signer-pin-modal')
    @include('layouts.components.signing-pin-missing-modal')
    @include('layouts.components.signing-certificate-missing-modal')

    @livewireScripts
</body>
</html>
