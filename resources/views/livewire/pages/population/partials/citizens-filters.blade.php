<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    <div class="flex flex-wrap items-center gap-2">
        @if($isSuperAdmin)
            <div class="w-full lg:w-auto lg:min-w-64">
                <x-ui.tenant-selector
                    :tenants="$tenants"
                    id="citizens-tenant"
                />
            </div>
        @endif

        <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
            <button
                type="button"
                wire:click="$set('statusFilter', 'active')"
                @class([
                    'rounded-lg px-3 py-2 text-sm font-medium transition',
                    'bg-white text-slate-900 shadow-sm' => $statusFilter === 'active',
                    'text-slate-500 hover:text-slate-900' => $statusFilter !== 'active',
                ])>
                Hidup
            </button>
            <button
                type="button"
                wire:click="$set('statusFilter', 'meninggal')"
                @class([
                    'rounded-lg px-3 py-2 text-sm font-medium transition',
                    'bg-white text-slate-900 shadow-sm' => $statusFilter === 'meninggal',
                    'text-slate-500 hover:text-slate-900' => $statusFilter !== 'meninggal',
                ])>
                Meninggal
            </button>
            <button
                type="button"
                wire:click="$set('statusFilter', '')"
                @class([
                    'rounded-lg px-3 py-2 text-sm font-medium transition',
                    'bg-white text-slate-900 shadow-sm' => $statusFilter === '',
                    'text-slate-500 hover:text-slate-900' => $statusFilter !== '',
                ])>
                Semua
            </button>
        </div>
    </div>

    <div class="relative w-full {{ $isSuperAdmin ? 'lg:max-w-md' : 'sm:max-w-md lg:ml-auto' }}">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
            <circle cx="11" cy="11" r="8" />
            <path stroke-linecap="round" d="m21 21-4.35-4.35" />
        </svg>

        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="Cari NIK atau nama..."
            class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
    </div>
</div>
