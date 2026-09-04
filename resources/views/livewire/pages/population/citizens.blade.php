<div class="space-y-6">
    @include('livewire.pages.population.partials.citizens-header')
    @include('livewire.pages.population.partials.citizens-filters')

    @if($selectedTenantId || auth()->user()->tenant_id)
        @if($showForm)
            @include('livewire.pages.population.partials.citizens-form')
        @endif

        @include('livewire.pages.population.partials.citizens-table')
    @endif
</div>
