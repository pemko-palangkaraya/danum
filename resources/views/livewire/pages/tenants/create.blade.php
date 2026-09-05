<div class="space-y-6">
    <x-ui.page-header
        title="Add Tenant"
        description="Tambahkan organisasi, kop surat, dan administrator awal ke DANUM."
        :back-url="route('tenants.index')"
        back-label="Back to tenants"
    />

    <form wire:submit="save" class="space-y-6">
        @include('livewire.pages.tenants.partials.create-basic')
        @include('livewire.pages.tenants.partials.create-letterhead')
        @include('livewire.pages.tenants.partials.create-location')
        @include('livewire.pages.tenants.partials.create-contact')
        @include('livewire.pages.tenants.partials.create-administrator')
        @include('livewire.pages.tenants.partials.create-footer')
    </form>
</div>
