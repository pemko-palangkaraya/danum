<?php

use App\Services\TenantService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function with(TenantService $tenantService): array
    {
        $tenant = $tenantService->find(request()->route('tenant'));

        abort_unless($tenant, 404);

        return [
            'tenant' => $tenant,
        ];
    }
};
?>

<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500">
                <a
                    href="{{ route('tenants.index') }}"
                    class="transition hover:text-slate-700">
                    Tenant Management
                </a>

                <span>/</span>

                <span class="text-slate-700">
                    View Tenant
                </span>
            </div>

            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">
                {{ $tenant->name }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Detail informasi tenant.
            </p>
        </div>

        <a
            href="{{ route('tenants.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">

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
                    d="M15 19l-7-7 7-7" />
            </svg>

            Back to Tenants
        </a>

    </div>


    {{-- Main Card --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- Card Header --}}
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Tenant Information
                    </p>

                    <p class="mt-1 font-mono text-sm font-semibold text-slate-700">
                        {{ $tenant->code }}
                    </p>
                </div>

                @php
                $status = $tenant->status?->value ?? $tenant->status ?? null;
                $statusString = (string) $status;

                $statusLabel = match ($statusString) {
                '1' => 'Active',
                '0' => 'Inactive',
                default => 'Unknown',
                };
                @endphp

                <span
                    @class([ 'inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold' , 'bg-emerald-50 text-emerald-700'=> $statusString === '1',
                    'bg-slate-100 text-slate-600' => $statusString === '0',
                    'bg-amber-50 text-amber-700' => !in_array($statusString, ['0', '1'], true),
                    ])>
                    {{ $statusLabel }}
                </span>

            </div>

        </div>


        {{-- Information --}}
        <div class="divide-y divide-slate-100">

            {{-- Location --}}
            <div class="px-5 py-5 sm:px-6">

                <h2 class="text-sm font-semibold text-slate-900">
                    Location
                </h2>

                <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            Province
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->province ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            City
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->city ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            District
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->district ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            Village
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->village ?: '—' }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <p class="text-xs font-medium text-slate-400">
                            Address
                        </p>

                        <p class="mt-1 text-sm leading-6 text-slate-700">
                            {{ $tenant->address ?: '—' }}
                        </p>
                    </div>

                </div>

            </div>


            {{-- Contact --}}
            <div class="px-5 py-5 sm:px-6">

                <h2 class="text-sm font-semibold text-slate-900">
                    Contact
                </h2>

                <div class="mt-4 grid gap-5 sm:grid-cols-2">

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            Phone
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->phone ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            Email
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->email ?: '—' }}
                        </p>
                    </div>

                </div>

            </div>


            {{-- Head --}}
            <div class="px-5 py-5 sm:px-6">

                <h2 class="text-sm font-semibold text-slate-900">
                    Organization Head
                </h2>

                <div class="mt-4 grid gap-5 sm:grid-cols-2">

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            Name
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->head_name ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium text-slate-400">
                            Title
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $tenant->head_title ?: '—' }}
                        </p>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>