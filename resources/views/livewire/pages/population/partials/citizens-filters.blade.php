<div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
    @if($isSuperAdmin)
        <div class="w-full lg:max-w-md">
            <x-ui.tenant-selector
                :tenants="$tenants"
                id="citizens-tenant"
            />
        </div>
    @endif

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
