<div class="space-y-6">
    @include('livewire.pages.population.partials.citizen-show-header')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            @include('livewire.pages.population.partials.citizen-show-identity')
        </div>

        @include('livewire.pages.population.partials.citizen-show-family')
    </div>

    @include('livewire.pages.population.partials.citizen-show-address-form')
    @include('livewire.pages.population.partials.citizen-show-address-history')
    @include('livewire.pages.population.partials.citizen-show-events')
</div>
