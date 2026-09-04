@props([
    'title',
    'description' => null,
    'action' => null,
    'backUrl' => null,
    'backLabel' => 'Kembali',
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-start gap-3">
        @if($backUrl)
            <a href="{{ $backUrl }}" class="mt-0.5 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="{{ $backLabel }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" /></svg>
            </a>
        @endif
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
            @if($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
        </div>
    </div>
    @if($action)
        {{ $action }}
    @endif
</div>
