@props([
    'user',
])

<div class="inline-flex items-center justify-end gap-2">
    <button
        type="button"
        wire:click="edit({{ $user->id }})"
        wire:loading.attr="disabled"
        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50">
        Edit
    </button>

    <button
        type="button"
        wire:click="toggleStatus({{ $user->id }})"
        wire:loading.attr="disabled"
        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition disabled:opacity-50 {{ $user->status === \App\Enums\UserStatus::ACTIVE ? 'bg-red-50 text-red-600 hover:bg-red-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' }}">
        {{ $user->status === \App\Enums\UserStatus::ACTIVE ? 'Deactivate' : 'Activate' }}
    </button>
</div>
