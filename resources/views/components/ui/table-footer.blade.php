@props([
    'paginator',
    'perPageModel' => 'perPage',
    'label' => 'data',
])

@if ($paginator->count())
    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center justify-between gap-4 sm:justify-start">
                <div class="flex items-center gap-2">
                    <label for="table-per-page-{{ $paginator->getPageName() }}" class="text-xs text-slate-500">Show</label>
                    <select
                        id="table-per-page-{{ $paginator->getPageName() }}"
                        wire:model.live="{{ $perPageModel }}"
                        class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                <p class="text-xs text-slate-500">
                    Showing
                    {{ $paginator->firstItem() }}
                    –
                    {{ $paginator->lastItem() }}
                    of
                    {{ $paginator->total() }}
                    {{ $label }}
                </p>
            </div>

            <x-ui.pagination :paginator="$paginator" />
        </div>
    </div>
@endif
