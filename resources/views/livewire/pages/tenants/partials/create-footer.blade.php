<x-ui.card padding="p-5 sm:p-6">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <x-ui.field label="Status" for="tenant-status" error="{{ $errors->first('status') }}">
            <select id="tenant-status" wire:model="status" class="form-select w-full sm:min-w-64">
                <option value="">Select status</option>
                @foreach (TenantStatus::cases() as $tenantStatus)
                    <option value="{{ $tenantStatus->value }}">{{ $tenantStatus->label() }}</option>
                @endforeach
            </select>
        </x-ui.field>

        <x-ui.form-actions class="pt-0">
            <x-ui.button type="button" wire:click="cancel" variant="secondary">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary" loading="save">Create Tenant & Administrator</x-ui.button>
        </x-ui.form-actions>
    </div>
</x-ui.card>
