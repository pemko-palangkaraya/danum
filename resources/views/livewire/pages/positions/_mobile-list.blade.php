<div class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:hidden">
    @forelse($positions as $position)
        @php($holder = $position->holders->first(fn ($item) => $item->ended_at === null && $item->started_at?->lte(now())))
        <div class="flex items-center justify-between gap-3 p-4">
            <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-semibold text-slate-900">{{ $position->name }}</div>
                <div class="mt-0.5 truncate text-xs text-slate-500">{{ $position->code }}</div>
            </div>
            <div class="min-w-0 max-w-[45%] text-right">
                <div class="truncate text-sm font-medium text-slate-800">{{ $holder?->user?->name ?? 'Belum ditentukan' }}</div>
                <div class="mt-1 flex justify-end gap-1.5">
                    @if($position->can_sign)<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">TTE</span>@endif
                    @if($position->can_validate)<span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">Verifikasi</span>@endif
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $position->status->value === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $position->status->value === 'active' ? 'Aktif' : 'Tidak Aktif' }}</span>
                </div>
            </div>
            <div class="shrink-0">
                <div
                    x-data="{ open: false, top: 0, left: 0, place() { this.$nextTick(() => { const trigger = this.$refs.trigger; const menu = this.$refs.menu; if (!trigger || !menu) return; const rect = trigger.getBoundingClientRect(); const gap = 8; const menuHeight = menu.offsetHeight; const menuWidth = menu.offsetWidth; let nextTop = rect.top - menuHeight - gap; if (nextTop < gap) nextTop = rect.bottom + gap; if (nextTop + menuHeight > window.innerHeight - gap) nextTop = Math.max(gap, window.innerHeight - menuHeight - gap); let nextLeft = rect.right - menuWidth; nextLeft = Math.max(gap, Math.min(nextLeft, window.innerWidth - menuWidth - gap)); this.top = nextTop; this.left = nextLeft; }); } }"
                    x-on:click.outside="open = false"
                    x-on:keydown.escape.window="open = false"
                    class="relative inline-block text-left"
                >
                    <button x-ref="trigger" type="button" x-on:click="open = !open; if (open) place()" x-bind:aria-expanded="open" aria-haspopup="menu" aria-label="Aksi jabatan" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
                    </button>
                    <div x-ref="menu" x-show="open" x-cloak x-transition x-bind:style="`top: ${top}px; left: ${left}px;`" class="fixed z-[100] w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-xl" role="menu">
                        <button type="button" x-on:click="open=false" wire:click="showHistoryFor('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">View</button>
                        <button type="button" x-on:click="open=false" wire:click="assignHolder('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">Pejabat</button>
                        @if($position->can_sign && $holder?->user)<button type="button" x-on:click="open=false" wire:click="manageCertificate('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-emerald-700 hover:bg-emerald-50">Sertifikat TTE</button>@endif
                        @if($isSuperAdmin)<button type="button" x-on:click="open=false" wire:click="edit('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">Edit</button>@endif
                        <button type="button" x-on:click="open=false" wire:click="toggleStatus('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm {{ $position->status->value === 'active' ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }}">{{ $position->status->value === 'active' ? 'Deactivate' : 'Activate' }}</button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="px-4 py-10 text-center text-sm text-slate-500">Belum ada jabatan.</div>
    @endforelse
</div>
