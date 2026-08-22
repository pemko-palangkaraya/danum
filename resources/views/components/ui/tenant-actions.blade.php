@props([
'tenant',
])

<div
    x-data="tenantActionMenu()"
    x-init="init()"
    @click.outside="close()"
    @keydown.escape.window="close()"
    class="relative inline-block text-left">
    <button
        type="button"
        @click="toggle()"
        :aria-expanded="open"
        aria-label="Tenant actions"
        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="h-5 w-5">
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
        </svg>
    </button>

    <div
        x-ref="menu"
        x-cloak
        x-show="open"
        class="absolute right-0 z-[9999] mt-2 w-20 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-lg md:fixed md:right-15 md:mt-0">

        @if ($tenant->trashed())

        <x-ui.action-menu-item
            label="Restore"
            variant="success"
            @click="close()"
            wire:click="confirmRestore('{{ $tenant->id }}')" />

        @else

        <x-ui.action-menu-item
            label="View"
            href="{{ route('tenants.show', $tenant->id) }}"
            @click="close()" />

        <x-ui.action-menu-item
            label="Edit"
            href="{{ route('tenants.edit', $tenant->id) }}"
            @click="close()" />

        <x-ui.action-menu-item
            label="Delete"
            variant="danger"
            @click="close()"
            wire:click="confirmDelete('{{ $tenant->id }}')" />

        @endif

    </div>
</div>