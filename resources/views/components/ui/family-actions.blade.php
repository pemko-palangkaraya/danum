@props([
    'family',
    'canManage' => false,
])

<div
    x-data="{
        open: false,
        menuTop: 0,
        menuLeft: 0,

        toggle() {
            this.open = !this.open;

            if (this.open) {
                this.position();
            }
        },

        close() {
            this.open = false;
        },

        position() {
            this.$nextTick(() => {
                const button = this.$refs.trigger;
                const menu = this.$refs.menu;

                if (!button || !menu) {
                    return;
                }

                const buttonRect = button.getBoundingClientRect();
                const menuWidth = menu.offsetWidth;
                const menuHeight = menu.offsetHeight;
                const gap = 8;
                const padding = 8;

                let left = buttonRect.right - menuWidth;
                let top = buttonRect.bottom + gap;

                if (top + menuHeight > window.innerHeight - padding) {
                    top = buttonRect.top - menuHeight - gap;
                }

                left = Math.max(
                    padding,
                    Math.min(
                        left,
                        window.innerWidth - menuWidth - padding
                    )
                );

                top = Math.max(
                    padding,
                    Math.min(
                        top,
                        window.innerHeight - menuHeight - padding
                    )
                );

                this.menuTop = top;
                this.menuLeft = left;
            });
        }
    }"
    @click.outside="close()"
    @keydown.escape.window="close()"
    @resize.window="open && position()"
    @scroll.window="open && position()"
    class="relative inline-block text-left">

    <button
        x-ref="trigger"
        type="button"
        @click="toggle()"
        :aria-expanded="open"
        aria-haspopup="true"
        aria-label="Family actions"
        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="h-5 w-5"
            aria-hidden="true">
            <circle cx="5" cy="12" r="1" />
            <circle cx="12" cy="12" r="1" />
            <circle cx="19" cy="12" r="1" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-ref="menu"
            x-show="open"
            x-cloak
            x-transition
            class="fixed z-[9999] w-36 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-lg"
            :style="{
                top: `${menuTop}px`,
                left: `${menuLeft}px`,
            }">
            <x-ui.action-menu-item
                label="Detail"
                @click="close()"
                wire:click="showDetail('{{ $family->id }}')"
                wire:loading.attr="disabled" />

            <x-ui.action-menu-item
                label="Cetak KK"
                href="{{ route('population.families.pdf', ['id' => $family->id]) }}"
                target="_blank"
                rel="noopener"
                @click="close()" />

            @if ($canManage)
                <x-ui.action-menu-item
                    label="Edit"
                    @click="close()"
                    wire:click="edit('{{ $family->id }}')"
                    wire:loading.attr="disabled" />
            @endif
        </div>
    </template>
</div>
