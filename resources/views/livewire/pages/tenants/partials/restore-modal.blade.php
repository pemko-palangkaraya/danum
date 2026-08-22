@if ($showRestoreConfirmation)

<div
    class="fixed inset-0 z-[10000] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true">

    {{-- Backdrop --}}
    <button
        type="button"
        wire:click="cancelRestore"
        class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]"
        aria-label="Close confirmation">
    </button>

    {{-- Dialog --}}
    <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">

        <div class="p-6">

            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-5 w-5">

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5 12l4 4L19 6" />

                </svg>

            </div>

            <h2 class="mt-4 text-lg font-semibold text-slate-900">
                Restore Tenant?
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Tenant yang dipilih akan dipulihkan dan kembali ditampilkan pada daftar tenant aktif.
            </p>

        </div>

        <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">

            <button
                type="button"
                wire:click="cancelRestore"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Cancel
            </button>

            <button
                type="button"
                wire:click="restoreTenant"
                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                Restore Tenant
            </button>

        </div>

    </div>

</div>

@endif