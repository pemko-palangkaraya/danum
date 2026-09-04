<x-ui.card>
    <x-slot:header>
        <h2 class="font-semibold text-slate-900">Identitas Penduduk</h2>
    </x-slot:header>

    <dl class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach([
            ['NIK', $citizen->nik], ['Nama Lengkap', $citizen->nama_lengkap], ['Tempat Lahir', $citizen->tempat_lahir ?: '-'],
            ['Tanggal Lahir', $citizen->tanggal_lahir?->format('d/m/Y') ?: '-'], ['Jenis Kelamin', $citizen->jenis_kelamin === 'male' ? 'Laki-laki' : ($citizen->jenis_kelamin === 'female' ? 'Perempuan' : '-')],
            ['Golongan Darah', $citizen->golongan_darah ?: '-'], ['Agama', $citizen->agama ?: '-'], ['Status Perkawinan', $citizen->status_perkawinan ?: '-'],
            ['Pendidikan', $citizen->pendidikan ?: '-'], ['Pekerjaan', $citizen->pekerjaan ?: '-'], ['Kewarganegaraan', $citizen->kewarganegaraan ?: '-'],
            ['Status Kependudukan', ucfirst($citizen->status_kependudukan)],
        ] as [$label, $value])
            <div>
                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</dt>
                <dd class="mt-1 text-sm font-medium text-slate-800">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
</x-ui.card>
