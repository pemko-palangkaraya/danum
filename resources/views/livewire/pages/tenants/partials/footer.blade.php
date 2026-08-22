@if ($tenants->count())

<div class="border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

        <div class="flex items-center justify-between gap-4 sm:justify-start">

            {{-- Per Page --}}
            <div class="flex items-center gap-2">
                <label
                    for="per-page"
                    class="text-xs text-slate-500">
                    Show
                </label>

                <select
                    id="per-page"
                    wire:model.live="perPage"
                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>

            {{-- Result Information --}}
            <p class="text-xs text-slate-500">
                Showing
                {{ $tenants->firstItem() }}
                –
                {{ $tenants->lastItem() }}
                of
                {{ $tenants->total() }}
                tenant{{ $tenants->total() === 1 ? '' : 's' }}
            </p>

        </div>

        {{-- Pagination --}}
        <x-ui.pagination :paginator="$tenants" />

    </div>

</div>

@endif