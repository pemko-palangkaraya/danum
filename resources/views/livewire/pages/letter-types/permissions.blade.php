<div class="space-y-6">
    @include('livewire.pages.letter-types.partials.permissions-header')
    @include('livewire.pages.letter-types.partials.permissions-categories')

    <x-ui.confirmation-modal
        modal-id="letter-type-category-permission-revoke"
        title="Cabut Akses Kategori"
        :message="'Apakah Anda yakin ingin mencabut akses '.$selectedCategoryName.' untuk jenis surat ini?'"
        confirm-text="Cabut Akses"
        cancel-text="Batal"
        confirm-action="revokeCategory"
        cancel-action="cancelRevokeCategory"
        variant="danger" />
</div>
