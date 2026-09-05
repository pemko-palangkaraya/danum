<div class="contents">
    <x-ui.field :label="'Provinsi'" :error="$errors->first($provinceModel)">
        <select wire:model.live="{{ $provinceModel }}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
            <option value="">Pilih provinsi</option>
            @foreach($provinces as $location)
                <option value="{{ $location }}">{{ $location }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field :label="'Kabupaten/Kota'" :error="$errors->first($cityModel)">
        <select wire:model.live="{{ $cityModel }}" @disabled($province === '') class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm disabled:bg-slate-50 disabled:text-slate-400">
            <option value="">{{ $province === '' ? 'Pilih provinsi dahulu' : 'Pilih kabupaten/kota' }}</option>
            @foreach($cities as $location)
                <option value="{{ $location }}">{{ $location }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field :label="'Kecamatan'" :error="$errors->first($districtModel)">
        <select wire:model.live="{{ $districtModel }}" @disabled($city === '') class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm disabled:bg-slate-50 disabled:text-slate-400">
            <option value="">{{ $city === '' ? 'Pilih kabupaten/kota dahulu' : 'Pilih kecamatan' }}</option>
            @foreach($districts as $location)
                <option value="{{ $location }}">{{ $location }}</option>
            @endforeach
        </select>
    </x-ui.field>

    <x-ui.field :label="'Kelurahan/Desa'" :error="$errors->first($villageModel)">
        <select wire:model.live="{{ $villageModel }}" @disabled($district === '') class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm disabled:bg-slate-50 disabled:text-slate-400">
            <option value="">{{ $district === '' ? 'Pilih kecamatan dahulu' : 'Pilih kelurahan/desa' }}</option>
            @foreach($villages as $location)
                <option value="{{ $location }}">{{ $location }}</option>
            @endforeach
        </select>
    </x-ui.field>

    @if($showPostalCode)
        <x-ui.field :label="'Kode Pos'" :error="$errors->first($postalModel)">
            <input wire:model="{{ $postalModel }}" inputmode="numeric" maxlength="10" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
        </x-ui.field>
    @endif
</div>
