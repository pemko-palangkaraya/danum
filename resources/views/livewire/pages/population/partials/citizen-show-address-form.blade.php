@if($canManage)
    <x-ui.card>
        <x-slot:header>
            <h2 class="font-semibold text-slate-900">Tambah Riwayat Alamat</h2>
            <p class="mt-1 text-sm text-slate-500">Alamat disimpan sebagai riwayat sehingga perubahan domisili tidak menghapus data sebelumnya.</p>
        </x-slot:header>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2 lg:col-span-4">
                <x-ui.field label="Alamat" :error="$errors->first('alamat')">
                    <textarea wire:model="alamat" rows="2" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></textarea>
                </x-ui.field>
            </div>

            @foreach([['rt','RT'],['rw','RW']] as [$field,$label])
                <x-ui.field :label="$label" :error="$errors->first($field)">
                    <input wire:model="{{ $field }}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                </x-ui.field>
            @endforeach

            <x-ui.population-location
                :provinces="$locationOptions['provinces']"
                :cities="$locationOptions['cities']"
                :districts="$locationOptions['districts']"
                :villages="$locationOptions['villages']"
                :province="$provinsi"
                :city="$kabupaten_kota"
                :district="$kecamatan"
            />

            <x-ui.field label="Berlaku Mulai" :error="$errors->first('berlaku_mulai')">
                <input wire:model="berlaku_mulai" type="date" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
            </x-ui.field>

            <x-ui.field label="Jenis Alamat" :error="$errors->first('jenis_alamat')">
                <select wire:model="jenis_alamat" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                    <option value="domisili">Domisili</option>
                    <option value="ktp">KTP</option>
                    <option value="asal">Asal</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </x-ui.field>
        </div>

        <x-slot:footer>
            <div class="flex justify-end">
                <x-ui.button wire:click="saveAddress">Simpan Alamat</x-ui.button>
            </div>
        </x-slot:footer>
    </x-ui.card>
@endif
