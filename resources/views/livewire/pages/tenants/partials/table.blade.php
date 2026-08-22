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
                    <x-ui.tenant-actions :tenant="$tenant" />
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