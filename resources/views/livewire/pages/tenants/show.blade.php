<?php

use App\Services\TenantService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function with(TenantService $tenantService): array
    {
        $tenant = $tenantService->find(request()->route('tenant'));
        abort_unless($tenant, 404);
        return ['tenant' => $tenant];
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a href="{{ route('tenants.index') }}" class="transition hover:text-slate-700">Tenant Management</a>
                <span>/</span><span class="text-slate-700">View Tenant</span>
            </div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">{{ $tenant->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">Detail informasi tenant.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('tenants.users', $tenant->id) }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Manage Users</a>
            <a href="{{ route('tenants.edit', $tenant->id) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Edit Tenant</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Tenant Information</p><p class="mt-1 font-mono text-sm font-semibold text-slate-700">{{ $tenant->code }}</p></div>
        <div class="divide-y divide-slate-100">
            <div class="px-5 py-5 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Location</h2><div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3"><div><p class="text-xs font-medium text-slate-400">Province</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->province ?: '—' }}</p></div><div><p class="text-xs font-medium text-slate-400">City</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->city ?: '—' }}</p></div><div><p class="text-xs font-medium text-slate-400">District</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->district ?: '—' }}</p></div><div><p class="text-xs font-medium text-slate-400">Village</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->village ?: '—' }}</p></div><div class="sm:col-span-2"><p class="text-xs font-medium text-slate-400">Address</p><p class="mt-1 text-sm leading-6 text-slate-700">{{ $tenant->address ?: '—' }}</p></div></div></div>
            <div class="px-5 py-5 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Contact</h2><div class="mt-4 grid gap-5 sm:grid-cols-2"><div><p class="text-xs font-medium text-slate-400">Phone</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->phone ?: '—' }}</p></div><div><p class="text-xs font-medium text-slate-400">Email</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->email ?: '—' }}</p></div></div></div>
            <div class="px-5 py-5 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Organization Head</h2><div class="mt-4 grid gap-5 sm:grid-cols-2"><div><p class="text-xs font-medium text-slate-400">Name</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->head_name ?: '—' }}</p></div><div><p class="text-xs font-medium text-slate-400">Title</p><p class="mt-1 text-sm text-slate-700">{{ $tenant->head_title ?: '—' }}</p></div></div></div>
        </div>
    </div>
</div>
