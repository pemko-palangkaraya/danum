@props([
    'title' => 'Tidak ada data',
    'description' => null,
    'action' => null,
])

<div {{ $attributes->class(['flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white px-6 py-12 text-center']) }}>
    <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-500">
        @isset($icon){{ $icon }}@else<span class="text-lg">—</span>@endisset
    </div>
    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if($description)<p class="mt-1 max-w-md text-sm text-slate-500">{{ $description }}</p>@endif
    @if($action)<div class="mt-4">{{ $action }}</div>@endif
</div>
