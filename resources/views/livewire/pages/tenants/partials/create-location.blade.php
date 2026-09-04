<x-ui.card>
    <x-slot:header>
        <h2 class="text-sm font-semibold text-slate-900">Location</h2>
    </x-slot:header>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (['province'=>'Province','city'=>'City','district'=>'District','village'=>'Village'] as $field => $label)
            <x-ui.input wire:model="{{ $field }}" :label="$label" id="tenant-{{ $field }}" error="{{ $errors->first($field) }}" />
        @endforeach

        <div class="sm:col-span-2 lg:col-span-4">
            <x-ui.textarea wire:model="address" label="Address" id="tenant-address" rows="3" error="{{ $errors->first('address') }}" />
        </div>
    </div>
</x-ui.card>
