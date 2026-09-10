<div class="space-y-6">
    @include('livewire.pages.tenant-profile.partials.header')
    @include('livewire.pages.tenant-profile.partials.notice')
    @include('livewire.pages.tenant-profile.partials.letterhead')
    @include('livewire.pages.tenant-profile.partials.identity')
    @include('livewire.pages.tenant-profile.partials.address')
    @include('livewire.pages.tenant-profile.partials.leadership')

    @if ($canUpdate)
        <div class="flex items-center justify-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="save">Simpan Perubahan</span>
                <span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    @endif
</div>
