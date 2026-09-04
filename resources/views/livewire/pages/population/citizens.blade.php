<div class="space-y-6">
    @include('livewire.pages.population.partials.citizens-header', [
        'isSuperAdmin' => $isSuperAdmin,
        'canView' => true,
        'canManage' => $canManage,
        'tenantSelected' => $tenantSelected,
    ])

    @include('livewire.pages.population.partials.citizens-filters', [
        'isSuperAdmin' => $isSuperAdmin,
        'tenants' => $tenants,
    ])

    @if($tenantSelected)
        @if($showForm)
            @include('livewire.pages.population.partials.citizens-form')
        @endif

        @include('livewire.pages.population.partials.citizens-table', [
            'canManage' => $canManage,
            'detailRoute' => $detailRoute,
        ])
    @endif
</div>
