<div class="space-y-6">
    @include('livewire.pages.dashboard.partials.header')

    @include('livewire.pages.dashboard.partials.outgoing-letters')

    @include('livewire.pages.dashboard.partials.population')

    @include('livewire.pages.dashboard.partials.workflow')

    @include('livewire.pages.dashboard.partials.tenant-breakdown')

    <div class="grid gap-6 lg:grid-cols-2">
        @include('livewire.pages.dashboard.partials.recent-letters')
        @include('livewire.pages.dashboard.partials.activities')
    </div>
</div>
