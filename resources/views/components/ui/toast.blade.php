@props([
    'duration' => 3500,
])

<div
    x-data="{
        show: @js(session()->has('toast')),
        type: @js(session('toast.type', 'success')),
        message: @js(session('toast.message', '')),
        timer: null,

        open(event) {
            this.type = event.detail?.type ?? 'success';
            this.message = event.detail?.message ?? '';

            if (!this.message) {
                return;
            }

            this.show = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.close(), {{ $duration }});
        },

        close() {
            this.show = false;
            clearTimeout(this.timer);
            this.timer = null;
        }
    }"
    x-init="if (show && message) { timer = setTimeout(() => close(), {{ $duration }}); }"
    x-on:toast.window="open($event)"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-4 opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-4 opacity-0"
    class="pointer-events-none fixed right-6 top-6 z-[100] w-[calc(100%-3rem)] max-w-[500px]"
    role="status"
    aria-live="polite">
    <div
        class="pointer-events-auto flex min-h-[70px] items-center gap-4 rounded-lg px-5 py-4 shadow-lg"
        :class="{
            'bg-[#12652d]': type === 'success',
            'bg-[#c62828]': type === 'error',
            'bg-[#d99b00]': type === 'warning',
            'bg-[#5269a8]': type === 'info',
        }">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/15 font-bold text-xl leading-none text-white" aria-hidden="true">
            <span x-show="type === 'success'">✓</span>
            <span x-show="type === 'error' || type === 'warning'">!</span>
            <span x-show="type === 'info'" class="text-lg font-semibold">i</span>
        </div>

        <div class="min-w-0 flex-1">
            <p x-text="message" class="text-[18px] font-medium leading-6 text-white"></p>
        </div>

        <button
            type="button"
            x-on:click="close()"
            class="shrink-0 rounded-md px-1.5 py-0.5 text-2xl font-light leading-none text-white/70 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"
            aria-label="Tutup notifikasi">×</button>
    </div>
</div>