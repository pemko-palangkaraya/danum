<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            <label for="user-per-page" class="text-xs text-slate-500">Show</label>
            <select id="user-per-page" wire:model.live="perPage" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>
        <p class="text-xs text-slate-500">Showing {{ $users->firstItem() }} – {{ $users->lastItem() }} of {{ $users->total() }} users</p>
    </div>
    <x-ui.pagination :paginator="$users" />
</div>
