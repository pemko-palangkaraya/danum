@if($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" wire:keydown.escape="resetForm">
        <div class="absolute inset-0" wire:click="resetForm"></div>

        <section class="relative z-10 max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl" role="dialog" aria-modal="true">
            <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">
                        {{ $editingRoleId ? ($editingSystemRole ? 'Edit System Role' : 'Edit Custom Role') : 'Create Custom Role' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $editingSystemRole ? 'Atur permission system role. Nama dan identitas role tetap dikunci.' : 'Pilih permission yang boleh diwariskan sesuai kewenangan actor.' }}
                    </p>
                </div>

                <button type="button" wire:click="resetForm" wire:loading.attr="disabled" aria-label="Tutup" title="Tutup" class="flex h-9 w-9 items-center justify-center rounded-lg text-2xl leading-none text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300 disabled:opacity-50">
                    &times;
                </button>
            </div>

            <div class="max-h-[calc(90vh-145px)] overflow-y-auto px-5 py-5 sm:px-6">
                <div>
                    <label class="text-sm font-medium text-slate-700">Role Name</label>
                    <input wire:model="name" type="text" maxlength="100" {{ $editingSystemRole ? 'readonly' : '' }} autofocus class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm {{ $editingSystemRole ? 'bg-slate-50 text-slate-500' : '' }}">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-6 space-y-4">
                    @foreach($this->groupedPermissions() as $module => $permissions)
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-800">
                                {{ str($module)->replace('-', ' ')->title() }}
                            </div>

                            <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($permissions as $permission)
                                    <label class="flex cursor-pointer items-center gap-3 bg-white px-4 py-3 hover:bg-slate-50">
                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission->id }}" class="rounded border-slate-300">
                                        <span>
                                            <span class="block text-sm font-medium text-slate-800">{{ $this->label($permission) }}</span>
                                            <span class="block text-[11px] text-slate-400">{{ $permission->slug }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">
                    Cancel
                </button>
                <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="save">Save Role</span>
                    <span wire:loading wire:target="save">Menyimpan...</span>
                </button>
            </div>
        </section>
    </div>
@endif
