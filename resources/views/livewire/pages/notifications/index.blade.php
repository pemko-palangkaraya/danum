<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Notifikasi</h1>
            <p class="mt-1 text-sm text-slate-500">Pemberitahuan penting dari sistem untuk akun dan tenant kamu.</p>
        </div>
        @if ($unreadCount > 0)
            <button wire:click="markAllAsRead" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Tandai semua sudah dibaca
            </button>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $isUnread = $notification->read_at === null;
                @endphp
                <button
                    type="button"
                    wire:click="openNotification('{{ $notification->id }}')"
                    class="flex w-full items-start gap-4 p-5 text-left transition hover:bg-slate-50 {{ $isUnread ? 'bg-blue-50/40' : 'bg-white' }}"
                >
                    <span class="mt-1.5 flex h-2.5 w-2.5 shrink-0 rounded-full {{ $isUnread ? 'bg-rose-500' : 'bg-slate-200' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-slate-900">{{ $data['title'] ?? 'Notifikasi' }}</span>
                            @if ($isUnread)
                                <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">Belum dibaca</span>
                            @endif
                        </span>
                        <span class="mt-1 block text-sm leading-6 text-slate-600">{{ $data['message'] ?? '' }}</span>
                        <span class="mt-2 block text-xs text-slate-400">{{ $notification->created_at?->format('d M Y, H:i') }}</span>
                    </span>
                    @if (! empty($data['action_url']))
                        <span class="mt-1 shrink-0 text-xs font-semibold text-indigo-600">Buka</span>
                    @endif
                </button>
            @empty
                <div class="p-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">—</div>
                    <p class="mt-4 text-sm font-medium text-slate-700">Belum ada notifikasi</p>
                    <p class="mt-1 text-sm text-slate-400">Pemberitahuan penting dari sistem akan muncul di sini.</p>
                </div>
            @endforelse
        </div>

        <x-ui.table-footer :paginator="$notifications" label="notifications" />
    </div>
</div>
