<div class="space-y-6">

    {{-- Page Header --}}
    @include('livewire.pages.tenants.partials.header')

    {{-- Main Card --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- Toolbar --}}
        @include('livewire.pages.tenants.partials.toolbar')

        {{-- DESKTOP TABLE --}}
        @include('livewire.pages.tenants.partials.table')

        {{-- MOBILE LIST --}}
        @include('livewire.pages.tenants.partials.mobile-list')

        {{-- FOOTER --}}
        @include('livewire.pages.tenants.partials.footer')

    </div>

    {{-- Delete Confirmation Modal --}}
    @include('livewire.pages.tenants.partials.delete-modal')

    {{-- Restore Confirmation Modal --}}
    @include('livewire.pages.tenants.partials.restore-modal')

</div>