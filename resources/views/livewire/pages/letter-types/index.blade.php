<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Letter Type Management</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola jenis surat dan template dokumen per tenant.</p>
        </div>
        <button wire:click="create" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Add Letter Type</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input wire:model.live="search" type="search" placeholder="Cari kode atau nama..." class="w-full rounded-xl border-slate-200 text-sm sm:max-w-sm">
            <select wire:model.live="filter" class="rounded-xl border-slate-200 text-sm sm:w-44">
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="validated">Validated</option>
                <option value="retired">Retired</option>
                <option value="deleted">Deleted</option>
            </select>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($letterTypes as $letterType)
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-semibold text-slate-400">{{ $letterType->code }}</span>
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $letterType->status->value }}</span>
                        </div>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ $letterType->name }}</h2>
                        <p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ $letterType->description ?: 'Tidak ada deskripsi.' }}</p>
                        <p class="mt-2 text-xs text-slate-400">Template version: {{ $letterType->versions()->first()?->version ?? 0 }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button wire:click="edit('{{ $letterType->id }}')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</button>
                        <button wire:click="delete('{{ $letterType->id }}')" wire:confirm="Hapus jenis surat ini?" class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50">Delete</button>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">Belum ada jenis surat.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-100 p-4">{{ $letterTypes->links() }}</div>
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit Letter Type' : 'Add Letter Type' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Perubahan template akan membuat version baru secara otomatis.</p>
                </div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="text-sm font-medium">Code</label><input wire:model="code" class="mt-1 w-full rounded-xl border-slate-200"><x-input-error :messages="$errors->get('code')" class="mt-1" /></div>
                        <div><label class="text-sm font-medium">Name</label><input wire:model="name" class="mt-1 w-full rounded-xl border-slate-200"><x-input-error :messages="$errors->get('name')" class="mt-1" /></div>
                    </div>
                    <div><label class="text-sm font-medium">Description</label><textarea wire:model="description" rows="2" class="mt-1 w-full rounded-xl border-slate-200"></textarea></div>
                    <div><label class="text-sm font-medium">Body Template</label><textarea wire:model="body_template" rows="10" placeholder="Yth. {{recipient_name}}..." class="mt-1 w-full rounded-xl border-slate-200 font-mono text-sm"></textarea><p class="mt-1 text-xs text-slate-400">Variable: {{ '{{number}}' }}, {{ '{{recipient_name}}' }}, {{ '{{recipient_address}}' }}, {{ '{{subject}}' }}, {{ '{{tenant_name}}' }}, {{ '{{tenant_city}}' }}, {{ '{{tenant_head_name}}' }}</p><x-input-error :messages="$errors->get('body_template')" class="mt-1" /></div>
                    <div><label class="text-sm font-medium">Status</label><select wire:model="status" class="mt-1 w-full rounded-xl border-slate-200"><option value="draft">Draft</option><option value="validated">Validated</option><option value="active">Active</option><option value="retired">Retired</option></select></div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Cancel</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Save</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
