<div class="relative space-y-6">
    <div wire:loading wire:target="file,preview,import" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 backdrop-blur-sm">
        <div class="mx-4 flex w-full max-w-sm items-center gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-xl">
            <div class="h-10 w-10 shrink-0 animate-spin rounded-full border-4 border-slate-200 border-t-slate-900"></div>
            <div>
                <p class="text-sm font-semibold text-slate-900">Sedang memproses...</p>
                <p class="mt-1 text-xs text-slate-500">Mohon tunggu, terutama untuk file Excel yang besar.</p>
            </div>
        </div>
    </div>

    @include('livewire.pages.population.partials.citizen-import-header', [
        'isSuperAdmin' => $isSuperAdmin,
    ])

    @include('livewire.pages.population.partials.citizen-import-form', [
        'isSuperAdmin' => $isSuperAdmin,
        'tenants' => $tenants,
    ])

    @include('livewire.pages.population.partials.citizen-import-preview')
</div>
