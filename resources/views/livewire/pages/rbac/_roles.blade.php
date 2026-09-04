<div class="space-y-4">
    @foreach($this->roles() as $role)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">{{ $role->name }}</h2>
                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[11px] font-semibold text-slate-700">
                            {{ $role->is_system ? 'SYSTEM' : 'CUSTOM' }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $role->scope === 'global' ? 'Global / seluruh tenant' : 'Tenant scoped' }} · {{ $role->slug }}
                    </p>
                </div>

                @php
                    $canEdit = $this->canManage()
                        && ((!$role->is_system && auth()->user()->isTenantAdmin())
                            || (auth()->user()->isSuperAdmin() && $role->slug !== 'super_admin'));
                @endphp

                @if($canEdit)
                    <div class="flex gap-2">
                        <button type="button" wire:click="openEdit({{ $role->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                            Edit Permissions
                        </button>

                        @if(auth()->user()->isSuperAdmin() && !$role->is_system)
                            <button type="button" wire:click="toggleActive({{ $role->id }})" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                                {{ $role->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($role->permissions as $permission)
                    <div class="bg-white px-5 py-3">
                        <p class="text-sm font-medium text-slate-800">{{ $permission->name }}</p>
                        <p class="text-[11px] text-slate-400">{{ $permission->slug }}</p>
                    </div>
                @empty
                    <div class="bg-white px-5 py-4 text-sm text-slate-500 sm:col-span-2 lg:col-span-3">
                        Tidak ada permission aktif.
                    </div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>
