<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-end lg:justify-between">
        @if(auth()->user()->isSuperAdmin())
            <div class="w-full lg:max-w-md">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</label>
                <select wire:model.live="selectedTenantId" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-700 shadow-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                    <option value="">Pilih tenant...</option>
                    @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}{{ $tenant->code ? ' ('.$tenant->code.')' : '' }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="flex w-full sm:justify-end">
            <div class="relative w-full sm:max-w-sm">
                <svg class="pointer-events-none absolute left-5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m20 20-3.5-3.5"></path>
                </svg>
                <input wire:model.live.debounce.300ms="search" placeholder="Cari No. KK atau kepala keluarga..." style="padding-left: 2.75rem;" class="w-full rounded-xl border border-slate-200 py-2.5 pr-4 text-sm shadow-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
            </div>
        </div>
    </div>
</div>
