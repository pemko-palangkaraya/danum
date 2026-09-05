@if ($paginator->hasPages())
    @php
        $pageName = $paginator->getPageName();
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $pages = collect([1, $lastPage])
            ->merge(range(max(1, $currentPage - 1), min($lastPage, $currentPage + 1)))
            ->unique()
            ->sort()
            ->values();
    @endphp

    <nav class="flex min-w-max items-center gap-1" aria-label="Pagination">
        <button
            type="button"
            wire:click="previousPage('{{ $pageName }}')"
            wire:loading.attr="disabled"
            @disabled($paginator->onFirstPage())
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            aria-label="Previous page">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </button>

        <div class="hidden items-center gap-1 sm:flex">
            @foreach ($pages as $index => $page)
                @if ($index > 0 && $page > $pages[$index - 1] + 1)
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center text-xs text-slate-400" aria-hidden="true">…</span>
                @endif

                <button
                    type="button"
                    wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                    @class([
                        'inline-flex h-8 min-w-8 shrink-0 items-center justify-center rounded-lg border px-2 text-xs font-medium transition',
                        'border-slate-900 bg-slate-900 text-white' => $currentPage === $page,
                        'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-900' => $currentPage !== $page,
                    ])>
                    {{ $page }}
                </button>
            @endforeach
        </div>

        <span class="inline-flex h-8 min-w-16 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 sm:hidden">
            {{ $currentPage }} / {{ $lastPage }}
        </span>

        <button
            type="button"
            wire:click="nextPage('{{ $pageName }}')"
            wire:loading.attr="disabled"
            @disabled(!$paginator->hasMorePages())
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
            aria-label="Next page">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="m9 18 6-6-6-6" />
            </svg>
        </button>
    </nav>
@endif
