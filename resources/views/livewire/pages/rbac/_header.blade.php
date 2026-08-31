<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm text-slate-500">Administration</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Role &amp; Access Control</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-500">Kelola system role dan custom role sesuai kewenangan.</p>
    </div>

    @if($this->canManage())
        <button type="button" wire:click="openCreate" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
            + Create Custom Role
        </button>
    @endif
</div>
