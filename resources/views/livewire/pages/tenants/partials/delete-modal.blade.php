    @if ($showDeleteConfirmation)
    <div
        class="fixed inset-0 z-[10000] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true">

        {{-- Backdrop --}}
        <button
            type="button"
            wire:click="cancelDelete"
            class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]"
            aria-label="Close confirmation">
        </button>

        {{-- Dialog --}}
        <div class="relative w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">

            <div class="p-6">

                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-red-600">
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
                            d="M12 9v4" />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 17h.01" />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.3 3.6 2.8 17a2 2 0 0 0 1.75 3h14.9a2 2 0 0 0 1.75-3l-7.5-13.4a2 2 0 0 0-3.5 0Z" />

                    </svg>
                </div>

                <h2 class="mt-4 text-lg font-semibold text-slate-900">
                    Delete Tenant?
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Tenant yang dipilih akan dihapus. Data tidak akan ditampilkan lagi pada daftar tenant.
                </p>

            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">

                <button
                    type="button"
                    wire:click="cancelDelete"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </button>

                <button
                    type="button"
                    wire:click="delete"
                    class="inline-flex items-center justify-center rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700">
                    Delete Tenant
                </button>

            </div>

        </div>
    </div>
    @endif