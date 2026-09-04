<x-ui.filter-bar align="between">
    <div class="flex rounded-xl bg-slate-100 p-1">
        <button type="button" wire:click="$set('filter','active')" class="rounded-lg px-4 py-2 text-sm font-medium {{ $filter === 'active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Active</button>
        <button type="button" wire:click="$set('filter','inactive')" class="rounded-lg px-4 py-2 text-sm font-medium {{ $filter === 'inactive' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Inactive</button>
    </div>

    <div class="relative w-full sm:w-80">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">⌕</span>
        <x-ui.input wire:model.live.debounce.300ms="search" type="search" placeholder="Search user..." class="py-2.5 pl-9 pr-3 text-sm" />
    </div>
</x-ui.filter-bar>
