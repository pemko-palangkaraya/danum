<div class="space-y-6">
    @include('livewire.positions._structure-header')
    @include('livewire.positions._structure-rules')
    @if(auth()->user()?->isSuperAdmin())
        @include('livewire.positions._structure-tenant-selector')
    @endif

    @if($selectedTenantId === '')
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">Pilih organisasi untuk melihat struktur.</div>
    @else
        @include('livewire.positions._structure-content')
        @include('livewire.positions._structure-form-modal')
        @include('livewire.positions._structure-holder-modal')
    @endif
</div>
