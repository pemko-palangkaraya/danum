<x-ui.card>
    <x-slot:header>
        <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Data Warga' : 'Tambah Data Warga' }}</h2>
        <p class="mt-1 text-sm text-slate-500">Lengkapi identitas, data keluarga, dan dokumen kependudukan.</p>
    </x-slot:header>

    <div class="space-y-7">
        <section>
            <h3 class="text-sm font-semibold text-slate-900">Identitas</h3>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([['nik','NIK'],['nama_lengkap','Nama Lengkap'],['tempat_lahir','Tempat Lahir'],['tanggal_lahir','Tanggal Lahir']] as [$field, $label])
                    <x-ui.input wire:model="{{ $field }}" type="{{ $field === 'tanggal_lahir' ? 'date' : 'text' }}" label="{{ $label }}" id="citizen-{{ $field }}" error="{{ $errors->first($field) }}" />
                @endforeach
                <x-ui.field label="Jenis Kelamin" for="citizen-jenis-kelamin">
                    <select id="citizen-jenis-kelamin" wire:model="jenis_kelamin" class="form-select w-full"><option value="">Pilih</option><option value="male">Laki-laki</option><option value="female">Perempuan</option></select>
                </x-ui.field>
                <x-ui.field label="Golongan Darah" for="citizen-golongan-darah">
                    <select id="citizen-golongan-darah" wire:model="golongan_darah" class="form-select w-full"><option value="">Pilih</option>@foreach(['A','B','AB','O','unknown'] as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select>
                </x-ui.field>
            </div>
        </section>

        <section class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-semibold text-slate-900">Data Keluarga</h3>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([['nama_ayah','Nama Ayah'],['nik_ayah','NIK Ayah'],['nama_ibu','Nama Ibu'],['nik_ibu','NIK Ibu'],['status_perkawinan','Status Perkawinan']] as [$field, $label])
                    <x-ui.input wire:model="{{ $field }}" label="{{ $label }}" id="citizen-{{ $field }}" error="{{ $errors->first($field) }}" />
                @endforeach
            </div>
        </section>

        <section class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-semibold text-slate-900">Kewarganegaraan & Dokumen</h3>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([['kewarganegaraan','Kewarganegaraan'],['no_passport','No. Passport'],['no_kitap','No. KITAP']] as [$field, $label])
                    <x-ui.input wire:model="{{ $field }}" label="{{ $label }}" id="citizen-{{ $field }}" error="{{ $errors->first($field) }}" />
                @endforeach
            </div>
        </section>

        <section class="border-t border-slate-100 pt-6">
            <h3 class="text-sm font-semibold text-slate-900">Pendidikan, Pekerjaan & Status</h3>
            <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach([['pendidikan','Pendidikan'],['pekerjaan','Pekerjaan'],['agama','Agama']] as [$field, $label])
                    <x-ui.input wire:model="{{ $field }}" label="{{ $label }}" id="citizen-{{ $field }}" error="{{ $errors->first($field) }}" />
                @endforeach
                <x-ui.field label="Status Kependudukan" for="citizen-status-kependudukan" error="{{ $errors->first('status_kependudukan') }}" required>
                    <select id="citizen-status-kependudukan" wire:model="status_kependudukan" class="form-select w-full"><option value="active">Aktif</option><option value="inactive">Tidak Aktif</option><option value="deceased">Meninggal</option><option value="moved">Pindah</option></select>
                </x-ui.field>
            </div>
        </section>
    </div>

    <x-slot:footer>
        <div class="flex justify-end gap-3">
            <x-ui.button type="button" wire:click="resetForm" variant="secondary">Batal</x-ui.button>
            <x-ui.button type="button" wire:click="save">Simpan</x-ui.button>
        </div>
    </x-slot:footer>
</x-ui.card>
