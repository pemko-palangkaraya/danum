<div class="hidden overflow-x-auto md:block">

    <table class="min-w-full divide-y divide-slate-200">

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

        <tbody class="divide-y divide-slate-100 bg-white">

            @forelse ($tenants as $tenant)

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
                    <x-ui.tenant-status :status="$tenant->status" />
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
                <td colspan="5" class="px-6 py-16 text-center">

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