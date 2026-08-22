@props([
'title' => 'Konfirmasi',
'message' => 'Apakah Anda yakin ingin melanjutkan?',
'confirmText' => 'Konfirmasi',
'cancelText' => 'Batal',
'confirmAction' => null,
'confirmButtonClass' => 'bg-red-600 hover:bg-red-700',
])

<div
    x-data="{ open: false }"
    @open-confirmation-modal.window="open = true"
    @close-confirmation-modal.window="open = false"
    x-show="open"
    x-cloak
    class="relative z-50"
    aria-labelledby="confirmation-modal-title"
    aria-modal="true"
    role="dialog">
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 bg-black/50"
        @click="open = false"></div>

    {{-- Modal --}}
    <div
        class="fixed inset-0 flex items-center justify-center p-4"
        @keydown.escape.window="open = false">
        <div
            x-show="open"
            x-transition
            @click.stop
            class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
            {{-- Header --}}
            <div class="px-6 pt-6">
                <h2
                    id="confirmation-modal-title"
                    class="text-lg font-semibold text-gray-900">
                    {{ $title }}
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-600">
                    {{ $message }}
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 px-6 py-5">
                <button
                    type="button"
                    @click="open = false"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                    {{ $cancelText }}
                </button>

                @if ($confirmAction)
                <button
                    type="button"
                    wire:click="{{ $confirmAction }}"
                    wire:loading.attr="disabled"
                    wire:target="{{ $confirmAction }}"
                    @click="open = false"
                    class="{{ $confirmButtonClass }} rounded-lg px-4 py-2 text-sm font-medium text-white transition disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading.remove wire:target="{{ $confirmAction }}">
                        {{ $confirmText }}
                    </span>

                    <span wire:loading wire:target="{{ $confirmAction }}">
                        Memproses...
                    </span>
                </button>
                @endif
            </div>
        </div>
    </div>
</div>