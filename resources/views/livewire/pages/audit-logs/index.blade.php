<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Super Admin</p>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Audit Log</h1>
            <p class="mt-1 text-sm text-slate-500">Telusuri aktivitas administratif dan perubahan data yang tercatat di sistem.</p>
        </div>
        <div class="text-sm text-slate-500">
            {{ number_format($logs->total()) }} aktivitas ditemukan
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-2">
                <label for="audit-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Pencarian</label>
                <input id="audit-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Actor, tenant, action, object, IP..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
            </div>

            <div>
                <label for="audit-actor" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Actor</label>
                <select id="audit-actor" wire:model.live="actor" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
                    <option value="">Semua actor</option>
                    @foreach ($actors as $item)
                        <option value="{{ $item->id }}">{{ $item->name }} — {{ $item->email }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="audit-tenant" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</label>
                <select id="audit-tenant" wire:model.live="tenant" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
                    <option value="">Semua tenant</option>
                    @foreach ($tenants as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}{{ $item->code ? ' — ' . $item->code : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="audit-action" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Action</label>
                <select id="audit-action" wire:model.live="action" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
                    <option value="">Semua action</option>
                    @foreach ($actions as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="audit-object" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Object</label>
                <input id="audit-object" type="text" wire:model.live.debounce.300ms="object" placeholder="Model / ID" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
            </div>

            <div>
                <label for="audit-date-from" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Dari tanggal</label>
                <input id="audit-date-from" type="date" wire:model.live="dateFrom" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
            </div>

            <div>
                <label for="audit-date-to" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai tanggal</label>
                <input id="audit-date-to" type="date" wire:model.live="dateTo" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-slate-400 focus:bg-white focus:ring-2 focus:ring-slate-100">
            </div>

            <div class="flex items-end">
                <button type="button" wire:click="resetFilters" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">Reset filter</button>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th class="px-5 py-3">Waktu</th>
                        <th class="px-5 py-3">Actor</th>
                        <th class="px-5 py-3">Tenant</th>
                        <th class="px-5 py-3">Action</th>
                        <th class="px-5 py-3">Object</th>
                        <th class="px-5 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-slate-900">{{ $log->user?->name ?? 'System / Unknown' }}</div>
                                @if ($log->user?->email)
                                    <div class="mt-0.5 text-xs text-slate-500">{{ $log->user->email }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600">
                                @if ($log->tenant)
                                    <div class="font-medium text-slate-800">{{ $log->tenant->name }}</div>
                                    @if ($log->tenant->code)<div class="text-xs text-slate-500">{{ $log->tenant->code }}</div>@endif
                                @else
                                    <span class="text-slate-400">Global</span>
                                @endif
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $log->action }}</span></td>
                            <td class="max-w-xs px-5 py-4">
                                <div class="truncate text-sm font-medium text-slate-800" title="{{ $log->auditable_type }}">{{ class_basename($log->auditable_type ?? '—') }}</div>
                                <div class="mt-0.5 truncate font-mono text-xs text-slate-500">{{ $log->auditable_id ?? '—' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <details class="max-w-sm">
                                    <summary class="cursor-pointer text-sm font-semibold text-slate-700 hover:text-slate-900">Before / After</summary>
                                    <div class="mt-3 space-y-3">
                                        <div>
                                            <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Before</p>
                                            <pre class="max-h-56 overflow-auto rounded-xl bg-slate-950 p-3 text-xs leading-5 text-slate-100">{{ json_encode($log->old_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                        <div>
                                            <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">After</p>
                                            <pre class="max-h-56 overflow-auto rounded-xl bg-slate-950 p-3 text-xs leading-5 text-slate-100">{{ json_encode($log->new_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 text-xs text-slate-500">
                                            <div><span class="font-semibold text-slate-700">IP:</span> {{ $log->ip_address ?? '—' }}</div>
                                            <div><span class="font-semibold text-slate-700">ID:</span> {{ $log->id }}</div>
                                        </div>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada audit log yang sesuai dengan filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            @forelse ($logs as $log)
                <details class="group p-4">
                    <summary class="cursor-pointer list-none">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $log->action }}</span>
                                    <span class="text-xs text-slate-400">{{ $log->created_at?->format('d M Y H:i') }}</span>
                                </div>
                                <div class="mt-2 truncate text-sm font-semibold text-slate-900">{{ $log->user?->name ?? 'System / Unknown' }}</div>
                                <div class="mt-0.5 truncate text-xs text-slate-500">{{ class_basename($log->auditable_type ?? '—') }} · {{ $log->auditable_id ?? '—' }}</div>
                            </div>
                            <span class="shrink-0 text-xs font-semibold text-slate-400 group-open:text-slate-700">Detail</span>
                        </div>
                    </summary>
                    <div class="mt-4 space-y-3 rounded-xl bg-slate-50 p-3">
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div><p class="text-slate-400">Tenant</p><p class="mt-0.5 font-semibold text-slate-700">{{ $log->tenant?->name ?? 'Global' }}</p></div>
                            <div><p class="text-slate-400">IP</p><p class="mt-0.5 font-mono font-semibold text-slate-700">{{ $log->ip_address ?? '—' }}</p></div>
                        </div>
                        <div>
                            <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">Before</p>
                            <pre class="max-h-48 overflow-auto rounded-xl bg-slate-950 p-3 text-xs leading-5 text-slate-100">{{ json_encode($log->old_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div>
                            <p class="mb-1 text-[11px] font-bold uppercase tracking-wide text-slate-400">After</p>
                            <pre class="max-h-48 overflow-auto rounded-xl bg-slate-950 p-3 text-xs leading-5 text-slate-100">{{ json_encode($log->new_values ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </details>
            @empty
                <div class="px-4 py-12 text-center text-sm text-slate-500">Belum ada audit log yang sesuai dengan filter.</div>
            @endforelse
        </div>

        @if ($logs->hasPages())
            <div class="border-t border-slate-200 px-4 py-4 sm:px-5">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
