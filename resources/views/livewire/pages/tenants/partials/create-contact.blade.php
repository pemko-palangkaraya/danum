<x-ui.card>
    <x-slot:header>
        <h2 class="text-sm font-semibold text-slate-900">Contact & Leadership</h2>
    </x-slot:header>

    <div class="grid gap-5 sm:grid-cols-2">
        @foreach (['phone'=>'Phone','email'=>'Email','head_name'=>'Head Name','head_title'=>'Head Title'] as $field => $label)
            <x-ui.input
                wire:model="{{ $field }}"
                :label="$label"
                id="tenant-{{ $field }}"
                :type="$field === 'email' ? 'email' : 'text'"
                error="{{ $errors->first($field) }}" />
        @endforeach
    </div>
</x-ui.card>
