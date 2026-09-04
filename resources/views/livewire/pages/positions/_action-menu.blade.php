<div
    x-data="{ open: false, top: 0, left: 0, place() { this.$nextTick(() => { const trigger = this.$refs.trigger; const menu = this.$refs.menu; if (!trigger || !menu) return; const rect = trigger.getBoundingClientRect(); const gap = 8; const menuHeight = menu.offsetHeight; const menuWidth = menu.offsetWidth; let nextTop = rect.top - menuHeight - gap; if (nextTop < gap) nextTop = rect.bottom + gap; if (nextTop + menuHeight > window.innerHeight - gap) nextTop = Math.max(gap, window.innerHeight - menuHeight - gap); let nextLeft = rect.right - menuWidth; nextLeft = Math.max(gap, Math.min(nextLeft, window.innerWidth - menuWidth - gap)); this.top = nextTop; this.left = nextLeft; }); } }"
    x-on:click.outside="open = false"
    x-on:keydown.escape.window="open = false"
    x-on:resize.window="if (open) place()"
    class="relative inline-block text-left"
>
    <button x-ref="trigger" type="button" x-on:click="open = !open; if (open) place()" x-bind:aria-expanded="open" aria-haspopup="menu" aria-label="Aksi jabatan" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
    </button>

    <div x-ref="menu" x-show="open" x-cloak x-transition x-bind:style="`top: ${top}px; left: ${left}px;`" class="fixed z-[100] w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-xl ring-1 ring-black/5" role="menu">
        <button type="button" x-on:click="open = false" wire:click="showHistoryFor('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" role="menuitem">View</button>
        <button type="button" x-on:click="open = false" wire:click="assignHolder('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" role="menuitem">Pejabat</button>
        @if($position->can_sign && $holder?->user)
            <button type="button" x-on:click="open = false" wire:click="manageCertificate('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-emerald-700 transition hover:bg-emerald-50 disabled:opacity-50" role="menuitem">Sertifikat TTE</button>
        @endif
        @if($isSuperAdmin)
            <button type="button" x-on:click="open = false" wire:click="edit('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" role="menuitem">Edit</button>
        @endif
        @if($position->status->value === 'active')
            <button type="button" x-on:click="open = false" wire:click="toggleStatus('{{ $position->id }}')" wire:confirm="Ubah status jabatan ini?" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50 disabled:opacity-50" role="menuitem">Deactivate</button>
        @else
            <button type="button" x-on:click="open = false" wire:click="toggleStatus('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50" role="menuitem">Activate</button>
        @endif
    </div>
</div>
