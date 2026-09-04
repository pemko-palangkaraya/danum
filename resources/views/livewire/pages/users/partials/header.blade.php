<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm text-slate-500">Administration</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Users</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola administrator dan pengguna seluruh tenant.</p>
    </div>
    <x-ui.button wire:click="create" variant="primary">
        ＋ Add User
    </x-ui.button>
</div>
