@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-end">
        <div class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m15 18-6-6 6-6" /></svg>
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-40" aria-label="{{ __('pagination.previous') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m15 18-6-6 6-6" /></svg>
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-xs font-medium text-slate-400">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-8 min-w-8 shrink-0 items-center justify-center rounded-lg border border-slate-900 bg-slate-900 px-2 text-xs font-semibold text-white">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex h-8 min-w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white px-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="{{ __('pagination.next') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m9 18 6-6-6-6" /></svg>
                </button>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-300" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4"><path d="m9 18 6-6-6-6" /></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
