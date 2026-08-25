<div
    x-data="{ open: false, detail: null }"
    x-on:audit-detail.window="detail = $event.detail; open = true"
    x-on:keydown.escape.window="open = false"
    class="space-y-6"
>
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
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Actor</th>
                        <th class="px-4 py-3">Tenant</th>
                        <th class="px-4 py-3">Action</th>
                        <th class="px-4 py-3">Object</th>
                        <th class="px-4 py-3 text-right">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                            <td class="max-w-44 px-4 py-3">
                                <div class="truncate text-sm font-semibold text-slate-900" title="{{ $log->user?->name ?? 'System / Unknown' }}">{{ $log->user?->name ?? 'System / Unknown' }}</div>
                                @if ($log->user?->email)
                                    <div class="mt-0.5 truncate text-xs text-slate-500" title="{{ $log->user->email }}">{{ $log->user->email }}</div>
                                @endif
                            </td>
                            <td class="max-w-40 px-4 py-3">
                                @if ($log->tenant)
                                    <div class="truncate text-sm font-medium text-slate-800" title="{{ $log->tenant->name }}">{{ $log->tenant->name }}</div>
                                    @if ($log->tenant->code)<div class="truncate text-xs text-slate-500">{{ $log->tenant->code }}</div>@endif
                                @else
                                    <span class="text-sm text-slate-400">Global</span>
                                @endif
                            </td>
                            <td class="px-4 py-3"><span class="inline-flex max-w-48 truncate rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" title="{{ $log->action }}">{{ $log->action }}</span></td>
                            <td class="max-w-48 px-4 py-3">
                                <div class="truncate text-sm font-medium text-slate-800" title="{{ $log->auditable_type }}">{{ class_basename($log->auditable_type ?? '—') }}</div>
                                <div class="mt-0.5 truncate font-mono text-xs text-slate-500" title="{{ $log->auditable_id ?? '—' }}">{{ $log->auditable_id ?? '—' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                                    x-on:click="$dispatch('audit-detail', {
                                        action: @js($log->action),
                                        createdAt: @js($log->created_at?->format('d M Y H:i:s')),
                                        actor: @js($log->user?->name ?? 'System / Unknown'),
                                        actorEmail: @js($log->user?->email),
                                        tenant: @js($log->tenant?->name ?? 'Global'),
                                        tenantCode: @js($log->tenant?->code),
                                        object: @js(class_basename($log->auditable_type ?? '—')),
                                        objectId: @js($log->auditable_id ?? '—'),
                                        oldValues: @js($log->old_values ?? []),
                                        newValues: @js($log->new_values ?? []),
                                        ip: @js($log->ip_address ?? '—'),
                                        id: @js($log->id),
                                    })"
                                >
                                    View
                                </button>
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
                <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex max-w-full truncate rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $log->action }}</span>
                                <span class="text-xs text-slate-400">{{ $log->created_at?->format('d M Y H:i') }}</span>
                            </div>
                            <div class="mt-2 truncate text-sm font-semibold text-slate-900">{{ $log->user?->name ?? 'System / Unknown' }}</div>
                            <div class="mt-0.5 truncate text-xs text-slate-500">{{ $log->tenant?->name ?? 'Global' }} · {{ class_basename($log->auditable_type ?? '—') }}</div>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm"
                            x-on:click="$dispatch('audit-detail', {
                                action: @js($log->action),
                                createdAt: @js($log->created_at?->format('d M Y H:i:s')),
                                actor: @js($log->user?->name ?? 'System / Unknown'),
                                actorEmail: @js($log->user?->email),
                                tenant: @js($log->tenant?->name ?? 'Global'),
                                tenantCode: @js($log->tenant?->code),
                                object: @js(class_basename($log->auditable_type ?? '—')),
                                objectId: @js($log->auditable_id ?? '—'),
                                oldValues: @js($log->old_values ?? []),
                                newValues: @js($log->new_values ?? []),
                                ip: @js($log->ip_address ?? '—'),
                                id: @js($log->id),
                            })"
                        >View</button>
                    </div>
                </div>
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

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
        x-on:click.self="open = false"
        role="dialog"
        aria-modal="true"
        aria-labelledby="audit-detail-title"
    >
        <div
            x-show="open"
            x-transition
            class="max-h-[90vh] w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 id="audit-detail-title" class="text-lg font-bold text-slate-900">Detail Audit Log</h2>
                        <span class="inline-flex max-w-full truncate rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700" x-text="detail?.action"></span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500" x-text="detail?.createdAt"></p>
                </div>
                <button type="button" x-on:click="open = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Tutup">✕</button>
            </div>

            <div class="max-h-[calc(90vh-80px)] space-y-5 overflow-y-auto p-5">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Actor</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800" x-text="detail?.actor || '—'"></p>
                        <p class="mt-0.5 truncate text-xs text-slate-500" x-text="detail?.actorEmail || ''"></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Tenant</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800" x-text="detail?.tenant || 'Global'"></p>
                        <p class="mt-0.5 text-xs text-slate-500" x-text="detail?.tenantCode || ''"></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Object</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800" x-text="detail?.object || '—'"></p>
                        <p class="mt-0.5 break-all font-mono text-xs text-slate-500" x-text="detail?.objectId || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">IP</p>
                        <p class="mt-1 break-all font-mono text-sm font-semibold text-slate-800" x-text="detail?.ip || '—'"></p>
                        <p class="mt-0.5 text-xs text-slate-500">Audit ID: <span x-text="detail?.id || '—'"></span></p>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Before</p>
                        </div>
                        <pre class="max-h-80 overflow-auto rounded-xl bg-slate-950 p-4 text-xs leading-5 text-slate-100" x-text="JSON.stringify(detail?.oldValues ?? {}, null, 2)"></pre>
                    </div>
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">After</p>
                        </div>
                        <pre class="max-h-80 overflow-auto rounded-xl bg-slate-950 p-4 text-xs leading-5 text-slate-100" x-text="JSON.stringify(detail?.newValues ?? {}, null, 2)"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
