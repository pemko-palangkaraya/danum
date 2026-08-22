@props([
'duration' => 3500,
])

<div
    x-data="{
        show: false,
        type: 'success',
        message: '',
        timer: null,

        open(event) {
            this.type = event.detail?.type ?? 'success';
            this.message = event.detail?.message ?? '';

            if (!this.message) {
                return;
            }

            this.show = true;

            clearTimeout(this.timer);

            this.timer = setTimeout(() => {
                this.close();
            }, {{ $duration }});
        },

        close() {
            this.show = false;

            clearTimeout(this.timer);

            this.timer = null;
        }
    }"
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

        {{-- Icon --}}
        <div class="flex h-8 w-8 shrink-0 items-center justify-center text-white">

            {{-- Success --}}
            <svg
                x-show="type === 'success'"
                xmlns="http://www.w3.org/2000/svg"
                class="h-8 w-8"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M12 22a10 10 0 100-20 10 10 0 000 20zm4.707-12.293a1 1 0 00-1.414-1.414L11 12.586l-2.293-2.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>

            {{-- Error --}}
            <svg
                x-show="type === 'error'"
                xmlns="http://www.w3.org/2000/svg"
                class="h-8 w-8"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M12 2a10 10 0 100 20 10 10 0 000-20zm3.707 6.293a1 1 0 00-1.414 0L12 10.586l-2.293-2.293a1 1 0 00-1.414 1.414L10.586 12l2.293 2.293a1 1 0 001.414-1.414L13.414 12l2.293-2.293a1 1 0 000-1.414z"
                    clip-rule="evenodd" />
            </svg>

            {{-- Warning --}}
            <svg
                x-show="type === 'warning'"
                xmlns="http://www.w3.org/2000/svg"
                class="h-8 w-8"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M10.29 3.86a2 2 0 013.42 0l8.18 14A2 2 0 0120.18 21H3.82a2 2 0 01-1.71-3.14l8.18-14zM12 8a1 1 0 011 1v4a1 1 0 11-2 0V9a1 1 0 011-1zm0 9a1.25 1.25 0 100-2.5A1.25 1.25 0 0012 17z"
                    clip-rule="evenodd" />
            </svg>

            {{-- Info --}}
            <svg
                x-show="type === 'info'"
                xmlns="http://www.w3.org/2000/svg"
                class="h-8 w-8"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 4a1.25 1.25 0 110 2.5A1.25 1.25 0 0112 6zm-1 5a1 1 0 102 0v6a1 1 0 10-2 0v-6z"
                    clip-rule="evenodd" />
            </svg>

        </div>

        {{-- Message --}}
        <div class="min-w-0 flex-1">
            <p
                x-text="message"
                class="text-[18px] font-medium leading-6 text-white"></p>
        </div>

        {{-- Close --}}
        <button
            type="button"
            x-on:click="close()"
            class="shrink-0 rounded-md p-1 text-white/70 transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-white/50"
            aria-label="Tutup notifikasi">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-7 w-7"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>

    </div>
</div>