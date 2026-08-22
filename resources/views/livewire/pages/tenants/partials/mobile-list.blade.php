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