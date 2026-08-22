@props([
'title' => 'Konfirmasi',
'message' => 'Apakah Anda yakin ingin melanjutkan?',
'confirmText' => 'Konfirmasi',
'cancelText' => 'Batal',
'confirmAction' => null,
'cancelAction' => null,
'variant' => 'danger',
'modalId' => null,
])

@php
$confirmButtonClasses = match ($variant) {
'danger' => 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
'warning' => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
'primary' => 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
'success' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
default => 'bg-gray-900 hover:bg-gray-800 focus:ring-gray-500',
};
@endphp

<div
    x-data="{ open: false }"
    x-show="open"
    x-cloak
    x-on:open-confirmation-modal.window="
        if ($event.detail?.id === '{{ $modalId }}') {
            open = true
        }
    "
    x-on:close-confirmation-modal.window="
        if ($event.detail?.id === '{{ $modalId }}') {
            open = false
        }
    "
    class="relative z-50"
    aria-labelledby="{{ $modalId }}-title"
    aria-modal="true"
    role="dialog">
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 bg-black/50"
        aria-hidden="true"></div>

    {{-- Modal --}}
    <div
        x-show="open"
        x-transition
        class="fixed inset-0 flex items-center justify-center p-4"
        x-on:keydown.escape.window="
            open = false
            @if ($cancelAction)
                $wire.{{ $cancelAction }}()
            @endif
        ">
        <div
            x-show="open"
            x-transition
            x-on:click.stop
            class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl">
            {{-- Content --}}
            <div class="px-6 pt-6">
                <h2
                    id="{{ $modalId }}-title"
                    class="text-lg font-semibold text-gray-900">
                    {{ $title }}
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-600">
                    {{ $message }}
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 px-6 py-5">

                {{-- Cancel --}}
                <button
                    type="button"
                    x-on:click="open = false"
                    @if ($cancelAction)
                    wire:click="{{ $cancelAction }}"
                    @endif
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                    {{ $cancelText }}
                </button>

                {{-- Confirm --}}
                @if ($confirmAction)
                <button
                    type="button"
                    x-on:click="open = false"
                    wire:click="{{ $confirmAction }}"
                    wire:loading.attr="disabled"
                    wire:target="{{ $confirmAction }}"
                    class="{{ $confirmButtonClasses }} rounded-lg px-4 py-2 text-sm font-medium text-white transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
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