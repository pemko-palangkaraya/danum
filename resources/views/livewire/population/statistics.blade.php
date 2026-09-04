<div class="space-y-6">
    @include('livewire.population.partials.statistics-header', [
        'isSuperAdmin' => $isSuperAdmin,
        'tenants' => $tenants,
    ])
    @include('livewire.population.partials.statistics-summary')
    @include('livewire.population.partials.statistics-age-pyramid')
    @include('livewire.population.partials.statistics-age-summary')
    @include('livewire.population.partials.statistics-demographics')
    @include('livewire.population.partials.statistics-age-pyramid-modal')
</div>
