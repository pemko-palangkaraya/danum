<x-ui.table-shell :responsive="false">
    <x-slot:toolbar>
        @include('livewire.pages.users.partials.filters')
    </x-slot:toolbar>

    <div class="hidden overflow-x-auto lg:block">
        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">User</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">NIP</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Tenant</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Role</th>
                    <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-5 py-4">
                            <div class="text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                            <div class="mt-0.5 text-xs text-slate-400">{{ $user->email }}</div>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $user->nip ?: '-' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $user->tenant?->name ?? 'System' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600">{{ $user->isSuperAdmin() ? 'Super Admin' : ($user->effectiveRole()?->name ?? '-') }}</td>
                        <td class="px-5 py-4">
                            <x-ui.badge :variant="$user->status === \App\Enums\UserStatus::ACTIVE ? 'success' : 'default'">
                                {{ strtolower($user->status->value) }}
                            </x-ui.badge>
                        </td>
                        <td class="px-5 py-4 text-right"><x-ui.user-actions :user="$user" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12">
                            <x-ui.empty-state title="No users found." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="divide-y divide-slate-100 lg:hidden">
        @forelse($users as $user)
            <div class="flex items-center justify-between gap-3 p-4">
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $user->email }} · {{ $user->tenant?->name ?? 'System' }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $user->isSuperAdmin() ? 'Super Admin' : ($user->effectiveRole()?->name ?? '-') }}</div>
                    <x-ui.badge class="mt-2" :variant="$user->status === \App\Enums\UserStatus::ACTIVE ? 'success' : 'default'">
                        {{ strtolower($user->status->value) }}
                    </x-ui.badge>
                </div>
                <div class="shrink-0"><x-ui.user-actions :user="$user" /></div>
            </div>
        @empty
            <div class="p-8">
                <x-ui.empty-state title="No users found." />
            </div>
        @endforelse
    </div>

    @if($users->total() > 0)
        <x-slot:footer>
            @include('livewire.pages.users.partials.footer')
        </x-slot:footer>
    @endif
</x-ui.table-shell>
