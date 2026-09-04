<div class="space-y-6">
    @include('livewire.population.families._header')
    @include('livewire.population.families._filters')

    @if($hasTenant)
        @include('livewire.population.families._form')
        @include('livewire.population.families._detail')
        @include('livewire.population.families._table')
    @elseif($isSuperAdmin)
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
            <p class="text-sm font-medium text-slate-700">Pilih tenant terlebih dahulu</p>
            <p class="mt-1 text-sm text-slate-500">Pilih tenant pada filter di atas untuk melihat data KK.</p>
        </div>
    @endif
</div>
