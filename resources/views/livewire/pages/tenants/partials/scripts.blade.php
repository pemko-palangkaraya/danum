<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('tenantActionMenu', () => ({
            open: false,
            cleanup: null,

            init() {
                this.cleanup = null;
            },

            async toggle() {
                this.open = !this.open;

                if (!this.open) {
                    this.cleanup?.();
                    this.cleanup = null;
                    return;
                }

                await this.$nextTick();

                const update = async () => {
                    const {
                        x,
                        y,
                    } = await window.DanumFloatingUI.computePosition(
                        this.$el.querySelector('button'),
                        this.$refs.menu, {
                            strategy: 'fixed',
                            placement: 'bottom-end',
                            middleware: [
                                window.DanumFloatingUI.offset(8),
                                window.DanumFloatingUI.flip(),
                                window.DanumFloatingUI.shift({
                                    padding: 8
                                }),
                            ],
                        },
                    );

                    Object.assign(this.$refs.menu.style, {
                        left: `${x}px`,
                        top: `${y}px`,
                    });
                };

                this.cleanup?.();

                this.cleanup = window.DanumFloatingUI.autoUpdate(
                    this.$el.querySelector('button'),
                    this.$refs.menu,
                    update,
                );

                await update();
            },

            close() {
                this.open = false;

                this.cleanup?.();
                this.cleanup = null;
            },
        }));
    });
</script>

{{-- Prevent Alpine flash before Alpine is initialized --}}
<style>
    [x-cloak] {
        display: none !important;
    }
</style>