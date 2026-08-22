<div class="border-b border-slate-200 p-4 sm:p-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        {{-- Filter --}}
        <div class="inline-flex w-fit items-center gap-1 rounded-xl bg-slate-100 p-1">

            <button
                type="button"
                wire:click="$set('filter', 'active')"
                @class([ 'rounded-lg px-3 py-2 text-sm font-semibold transition' , 'bg-white text-slate-900 shadow-sm'=> $filter === 'active',
                'text-slate-500 hover:text-slate-700' => $filter !== 'active',
                ])
                >
                Active
            </button>

            <button
                type="button"
                wire:click="$set('filter', 'deleted')"
                @class([ 'rounded-lg px-3 py-2 text-sm font-semibold transition' , 'bg-white text-slate-900 shadow-sm'=> $filter === 'deleted',
                'text-slate-500 hover:text-slate-700' => $filter !== 'deleted',
                ])
                >
                Deleted
            </button>

        </div>

        {{-- Search --}}
        <div class="relative w-full sm:max-w-md">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                <circle
                    cx="11"
                    cy="11"
                    r="8" />

                <path
                    stroke-linecap="round"
                    d="m21 21-4.35-4.35" />
            </svg>

            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Search tenant..."
                class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">

        </div>

    </div>
</div>