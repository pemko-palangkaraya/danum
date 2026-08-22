@if ($paginator->hasPages())

{{-- Desktop --}}
<div class="hidden items-center gap-1 sm:flex">

    {{-- Previous --}}
    <button
        type="button"
        wire:click="previousPage"
        wire:loading.attr="disabled"
        @disabled($paginator->onFirstPage())
        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
        aria-label="Previous page">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="h-4 w-4">
            <path d="m15 18-6-6 6-6" />
        </svg>

    </button>

    {{-- Page Numbers --}}
    @for ($page = 1; $page <= $paginator->lastPage(); $page++)

        <button
            type="button"
            wire:click="gotoPage({{ $page }})"
            class="inline-flex h-8 min-w-8 shrink-0 items-center justify-center rounded-lg border px-2 text-xs font-medium transition
                    {{ $paginator->currentPage() === $page
                        ? 'border-slate-900 bg-slate-900 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">

            {{ $page }}

        </button>

        @endfor

        {{-- Next --}}
        <button
            type="button"
            wire:click="nextPage"
            wire:loading.attr="disabled"
            @disabled(!$paginator->hasMorePages())
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            aria-label="Next page">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-4 w-4">
                <path d="m9 18 6-6-6-6" />
            </svg>

        </button>

</div>


{{-- Mobile --}}
<div class="flex items-center gap-2 sm:hidden">

    {{-- Previous --}}
    <button
        type="button"
        wire:click="previousPage"
        wire:loading.attr="disabled"
        @disabled($paginator->onFirstPage())
        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
        aria-label="Previous page">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="h-4 w-4">
            <path d="m15 18-6-6 6-6" />
        </svg>

    </button>

    {{-- Current Page --}}
    <span class="inline-flex h-8 min-w-16 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600">
        {{ $paginator->currentPage() }}
        /
        {{ $paginator->lastPage() }}
    </span>

    {{-- Next --}}
    <button
        type="button"
        wire:click="nextPage"
        wire:loading.attr="disabled"
        @disabled(!$paginator->hasMorePages())
        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
        aria-label="Next page">

        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="h-4 w-4">
            <path d="m9 18 6-6-6-6" />
        </svg>

    </button>

</div>

@endif