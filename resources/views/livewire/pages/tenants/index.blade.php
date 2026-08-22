<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                Tenant Management
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Kelola organisasi yang menggunakan DANUM.
            </p>
        </div>

        <a
            href="{{ route('tenants.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="h-4 w-4">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 5v14" />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M5 12h14" />
            </svg>

            Add Tenant
        </a>
    </div>

    {{-- Main Card --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- Toolbar --}}
        <div class="border-b border-slate-200 p-4 sm:p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                {{-- Filter --}}
                <div class="inline-flex w-fit items-center gap-1 rounded-xl bg-slate-100 p-1">
                    <button
                        type="button"
                        wire:click="$set('filter', 'active')"
                        @class([ 'rounded-lg px-3 py-2 text-sm font-semibold transition' , 'bg-white text-slate-900 shadow-sm'=> $filter === 'active',
                        'text-slate-500 hover:text-slate-700' => $filter !== 'active',
                        ])
                        >
                        Active
                    </button>

                    <button
                        type="button"
                        wire:click="$set('filter', 'deleted')"
                        @class([ 'rounded-lg px-3 py-2 text-sm font-semibold transition' , 'bg-white text-slate-900 shadow-sm'=> $filter === 'deleted',
                        'text-slate-500 hover:text-slate-700' => $filter !== 'deleted',
                        ])
                        >
                        Deleted
                    </button>
                </div>

                {{-- Search --}}
                <div class="relative w-full sm:max-w-md">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                        <circle
                            cx="11"
                            cy="11"
                            r="8" />

                        <path
                            stroke-linecap="round"
                            d="m21 21-4.35-4.35" />
                    </svg>

                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search tenant..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
                </div>

            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- DESKTOP TABLE --}}
        {{-- ========================================================= --}}

        <div class="hidden overflow-x-auto md:block">

            <table class="min-w-full divide-y divide-slate-200">

                {{-- Table Header --}}
                <thead class="bg-slate-50">
                    <tr>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Code
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Tenant
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Location
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Status
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                            Action
                        </th>

                    </tr>
                </thead>

                {{-- Table Body --}}
                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse ($tenants as $tenant)

                    @php
                    $status = $tenant->status?->value ?? $tenant->status ?? null;

                    $statusString = (string) $status;

                    $statusLabel = match ($statusString) {
                    '1' => 'Active',
                    '0' => 'Inactive',
                    default => 'Unknown',
                    };
                    @endphp

                    <tr class="transition hover:bg-slate-50/70">

                        {{-- Code --}}
                        <td class="whitespace-nowrap px-6 py-4">
                            <span class="font-mono text-sm font-semibold text-slate-700">
                                {{ $tenant->code }}
                            </span>
                        </td>

                        {{-- Tenant --}}
                        <td class="px-6 py-4">

                            <div class="font-medium text-slate-900">
                                {{ $tenant->name }}
                            </div>

                            @if ($tenant->email)
                            <div class="mt-0.5 text-xs text-slate-500">
                                {{ $tenant->email }}
                            </div>
                            @endif

                        </td>

                        {{-- Location --}}
                        <td class="px-6 py-4">

                            <div class="text-sm text-slate-700">
                                {{ $tenant->city ?: '—' }}
                            </div>

                            @if ($tenant->province)
                            <div class="text-xs text-slate-500">
                                {{ $tenant->province }}
                            </div>
                            @endif

                        </td>

                        {{-- Status --}}
                        <td class="whitespace-nowrap px-6 py-4">

                            <span
                                @class([ 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold' , 'bg-emerald-50 text-emerald-700'=> $statusString === '1',
                                'bg-slate-100 text-slate-600' => $statusString === '0',
                                'bg-amber-50 text-amber-700' => !in_array(
                                $statusString,
                                ['0', '1'],
                                true
                                ),
                                ])
                                >
                                {{ $statusLabel }}
                            </span>

                        </td>

                        {{-- Action --}}
                        <td class="whitespace-nowrap px-6 py-4 text-right">

                            <div
                                x-data="tenantActionMenu()"
                                x-init="init()"
                                @click.outside="close()"
                                @keydown.escape.window="close()"
                                class="relative inline-block text-left">

                                <button
                                    type="button"
                                    @click="toggle()"
                                    :aria-expanded="open"
                                    aria-label="Tenant actions"
                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        class="h-5 w-5">

                                        <circle cx="5" cy="12" r="1" />
                                        <circle cx="12" cy="12" r="1" />
                                        <circle cx="19" cy="12" r="1" />

                                    </svg>

                                </button>

                                <div
                                    x-ref="menu"
                                    x-cloak
                                    x-show="open"
                                    class="fixed z-[9999] w-20 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-lg">

                                    @if ($tenant->trashed())

                                    <button
                                        type="button"
                                        @click="close()"
                                        wire:click="confirmRestore('{{ $tenant->id }}')"
                                        class="block w-full px-4 py-2.5 text-left text-sm text-emerald-600 transition hover:bg-emerald-50">
                                        Restore
                                    </button>

                                    @else

                                    <a
                                        href="{{ route('tenants.show', $tenant->id) }}"
                                        @click="close()"
                                        class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                        View
                                    </a>

                                    <a
                                        href="{{ route('tenants.edit', $tenant->id) }}"
                                        @click="close()"
                                        class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                        Edit
                                    </a>

                                    <button
                                        type="button"
                                        @click="close()"
                                        wire:click="confirmDelete('{{ $tenant->id }}')"
                                        class="block w-full px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                        Delete
                                    </button>

                                    @endif

                                </div>

                            </div>

                        </td>

                    </tr>

                    @empty

                    <tr>
                        <td
                            colspan="5"
                            class="px-6 py-16 text-center">

                            <div class="mx-auto max-w-sm">

                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        class="h-6 w-6 text-slate-400">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3 21h18" />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 21V7l7-4 7 4v14" />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9 21v-4h6v4" />
                                    </svg>

                                </div>

                                <h3 class="mt-4 text-sm font-semibold text-slate-900">
                                    No tenants found
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Try changing your search or add a new tenant.
                                </p>

                            </div>

                        </td>
                    </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- ========================================================= --}}
        {{-- MOBILE LIST --}}
        {{-- ========================================================= --}}

        <div class="divide-y divide-slate-100 md:hidden">

            @forelse ($tenants as $tenant)

            @php
            $status = $tenant->status?->value ?? $tenant->status ?? null;

            $statusString = (string) $status;

            $statusLabel = match ($statusString) {
            '1' => 'Active',
            '0' => 'Inactive',
            default => 'Unknown',
            };
            @endphp

            <div class="p-4">

                <div class="flex items-start justify-between gap-4">

                    {{-- Tenant Info --}}
                    <div class="min-w-0">

                        <div class="flex items-center gap-2">

                            <span class="font-mono text-xs font-semibold text-slate-500">
                                {{ $tenant->code }}
                            </span>

                            <span
                                @class([ 'inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold' , 'bg-emerald-50 text-emerald-700'=> $statusString === '1',
                                'bg-slate-100 text-slate-600' => $statusString === '0',
                                'bg-amber-50 text-amber-700' => !in_array(
                                $statusString,
                                ['0', '1'],
                                true
                                ),
                                ])
                                >
                                {{ $statusLabel }}
                            </span>

                        </div>

                        <h3 class="mt-1 truncate text-sm font-semibold text-slate-900">
                            {{ $tenant->name }}
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">

                            {{ $tenant->city ?: '—' }}

                            @if ($tenant->province)
                            · {{ $tenant->province }}
                            @endif

                        </p>

                    </div>

                    {{-- Mobile Action --}}
                    <div
                        x-data="{
                                    open: false,
                                    close() {
                                        this.open = false;
                                    }
                                }"
                        @click.outside="close()"
                        @keydown.escape.window="close()"
                        class="relative shrink-0">

                        {{-- Three Dots --}}
                        <button
                            type="button"
                            @click="open = !open"
                            :aria-expanded="open"
                            aria-label="Tenant actions"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                class="h-5 w-5">
                                <circle
                                    cx="5"
                                    cy="12"
                                    r="1" />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="1" />

                                <circle
                                    cx="19"
                                    cy="12"
                                    r="1" />
                            </svg>
                        </button>

                        {{-- Dropdown --}}
                        <div
                            x-cloak
                            x-show="open"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute right-0 z-50 mt-2 w-20 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-lg">

                            @if ($tenant->trashed())

                            <button
                                type="button"
                                @click="close()"
                                wire:click="confirmRestore('{{ $tenant->id }}')"
                                class="block w-full px-4 py-2.5 text-left text-sm text-emerald-600 transition hover:bg-emerald-50">
                                Restore
                            </button>

                            @else

                            <a
                                href="{{ route('tenants.show', $tenant->id) }}"
                                @click="close()"
                                class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                View
                            </a>

                            <a
                                href="{{ route('tenants.edit', $tenant->id) }}"
                                @click="close()"
                                class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                Edit
                            </a>

                            <button
                                type="button"
                                @click="close()"
                                wire:click="confirmDelete('{{ $tenant->id }}')"
                                class="block w-full px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50">
                                Delete
                            </button>

                            @endif

                        </div>

                    </div>

                </div>

            </div>

            @empty

            <div class="px-4 py-12 text-center">

                <h3 class="text-sm font-semibold text-slate-900">
                    No tenants found
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Try changing your search.
                </p>

            </div>

            @endforelse

        </div>

        {{-- ========================================================= --}}
        {{-- FOOTER --}}
        {{-- ========================================================= --}}

        @if ($tenants->count())

        <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div class="flex items-center justify-between gap-4 sm:justify-start">

                    {{-- Per Page --}}
                    <div class="flex items-center gap-2">
                        <label
                            for="per-page"
                            class="text-xs text-slate-500">
                            Show
                        </label>

                        <select
                            id="per-page"
                            wire:model.live="perPage"
                            class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>

                    {{-- Result Information --}}
                    <p class="text-xs text-slate-500">
                        Showing
                        {{ $tenants->firstItem() }}
                        –
                        {{ $tenants->lastItem() }}
                        of
                        {{ $tenants->total() }}
                        tenant{{ $tenants->total() === 1 ? '' : 's' }}
                    </p>

                </div>

                {{-- Pagination --}}
                <x-ui.pagination :paginator="$tenants" />

            </div>

        </div>

        @endif

    </div>

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
</div>

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