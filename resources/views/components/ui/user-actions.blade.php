@props([
    'user',
])

<div
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative inline-block text-left">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="User actions"
        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true">
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute right-0 z-50 mt-2 w-40 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-lg">
        <button
            type="button"
            @click="open = false"
            wire:click="edit({{ $user->id }})"
            wire:loading.attr="disabled"
            class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50">
            Edit
        </button>

        <button
            type="button"
            @click="open = false"
            wire:click="toggleStatus({{ $user->id }})"
            wire:loading.attr="disabled"
            class="block w-full px-4 py-2.5 text-left text-sm transition disabled:opacity-50 {{ $user->status === \App\Enums\UserStatus::ACTIVE ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
            {{ $user->status === \App\Enums\UserStatus::ACTIVE ? 'Deactivate' : 'Activate' }}
        </button>
    </div>
</div>
