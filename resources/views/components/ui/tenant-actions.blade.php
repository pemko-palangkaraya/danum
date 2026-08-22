@props([
    'tenant',
])

<div class="inline-flex items-center gap-1">
    @if (! $tenant->trashed())
        <a
            href="{{ route('tenants.show', $tenant->id) }}"
            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
            View
        </a>

        <a
            href="{{ route('tenants.edit', $tenant->id) }}"
            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
            Edit
        </a>

        <button
            type="button"
            wire:click="confirmDelete('{{ $tenant->id }}')"
            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
            Delete
        </button>
    @else
        <button
            type="button"
            wire:click="confirmRestore('{{ $tenant->id }}')"
            class="rounded-lg px-2.5 py-1.5 text-xs font-medium text-emerald-600 transition hover:bg-emerald-50 hover:text-emerald-700">
            Restore
        </button>
    @endif
</div>
