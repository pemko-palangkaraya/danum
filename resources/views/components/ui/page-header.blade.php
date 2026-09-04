@props([
    'title',
    'description' => null,
    'action' => null,
])

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
        @if($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
    </div>
    @if($action)
        {{ $action }}
    @endif
</div>
