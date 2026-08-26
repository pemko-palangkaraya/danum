@props([
    'user',
])

<div
    x-data="{
        open: false,
        top: 0,
        left: 0,
        menuWidth: 160,
        gap: 8,
        positionMenu() {
            const trigger = this.$refs.trigger;
            if (!trigger) return;

            const rect = trigger.getBoundingClientRect();
            const menuHeight = 144;
            const viewportPadding = 8;

            let nextLeft = rect.right - this.menuWidth;
            let nextTop = rect.top - menuHeight - this.gap;

            if (nextTop < viewportPadding) {
                nextTop = rect.bottom + this.gap;
            }

            nextLeft = Math.max(
                viewportPadding,
                Math.min(nextLeft, window.innerWidth - this.menuWidth - viewportPadding)
            );

            this.left = nextLeft;
            this.top = nextTop;
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.positionMenu());
            }
        },
        close() {
            this.open = false;
        }
    }"
    @click.outside="close()"
    @keydown.escape.window="close()"
    @resize.window="open && positionMenu()"
    @scroll.window="open && positionMenu()"
    class="inline-block text-left">
    <button
        x-ref="trigger"
        type="button"
        @click="toggle()"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="User actions"
        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true">
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition
            @click.outside="close()"
            class="fixed z-[9999] w-40 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-lg"
            :style="`top: ${top}px; left: ${left}px;`">
            <button
                type="button"
                @click="close()"
                wire:click="edit({{ $user->id }})"
                wire:loading.attr="disabled"
                class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50">
                Edit
            </button>

            <button
                type="button"
                @click="close()"
                wire:click="openSignerPin({{ $user->id }})"
                wire:loading.attr="disabled"
                class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50">
                PIN Tanda Tangan
            </button>

            <button
                type="button"
                @click="close()"
                wire:click="toggleStatus({{ $user->id }})"
                wire:loading.attr="disabled"
                class="block w-full px-4 py-2.5 text-left text-sm transition disabled:opacity-50 {{ $user->status === \App\Enums\UserStatus::ACTIVE ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }}">
                {{ $user->status === \App\Enums\UserStatus::ACTIVE ? 'Deactivate' : 'Activate' }}
            </button>
        </div>
    </template>
</div>
