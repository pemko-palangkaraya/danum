@props([
    'column',
    'label',
    'sortBy' => '',
    'sortDirection' => 'asc',
])

<button
    type="button"
    wire:click="sort('{{ $column }}')"
    wire:loading.attr="disabled"
    class="group inline-flex items-center gap-1.5 text-left font-semibold uppercase tracking-wide text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
>
    <span>{{ $label }}</span>
    <span class="inline-flex w-4 justify-center text-[11px] leading-none" aria-hidden="true">
        @if ($sortBy === $column)
            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
        @else
            <span class="opacity-35 transition group-hover:opacity-80">↕</span>
        @endif
    </span>
    <span class="sr-only">
        {{ $sortBy === $column ? ($sortDirection === 'asc' ? 'Urutan naik' : 'Urutan turun') : 'Urutkan' }}
    </span>
</button>
