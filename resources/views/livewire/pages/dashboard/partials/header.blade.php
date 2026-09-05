<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <p class="text-sm font-medium text-slate-400">{{ $isSuperAdmin ? 'Platform Administration' : 'Workspace' }}</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
            Selamat datang, {{ auth()->user()->name }}. Ringkasan kondisi {{ $isSuperAdmin ? 'platform DANUM' : 'organisasi '.$tenantName }}.
        </p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $isSuperAdmin ? 'Scope' : 'Organisasi' }}</p>
        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $tenantName }}</p>
    </div>
</div>
