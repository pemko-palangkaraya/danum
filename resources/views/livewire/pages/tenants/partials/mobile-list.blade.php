        <div class="divide-y divide-slate-100 md:hidden">

            @forelse ($tenants as $tenant)

            <div class="p-4">

                <div class="flex items-start justify-between gap-4">

                    {{-- Tenant Info --}}
                    <div class="min-w-0">

                        <div class="flex items-center gap-2">

                            <span class="font-mono text-xs font-semibold text-slate-500">
                                {{ $tenant->code }}
                            </span>
                            <x-ui.tenant-status :status="$tenant->status" />

                        </div>

                        <h3 class="mt-1 truncate text-sm font-semibold text-slate-900">
                            {{ $tenant->name }}
                        </h3>
                        <p class="mt-0.5 truncate text-xs font-medium text-slate-500">
                            {{ $tenant->category?->name ?? 'Tanpa kategori' }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">

                            {{ $tenant->city ?: '—' }}

                            @if ($tenant->province)
                            · {{ $tenant->province }}
                            @endif

                        </p>

                    </div>

                    {{-- Mobile Action --}}
                    <x-ui.tenant-actions :tenant="$tenant" />

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