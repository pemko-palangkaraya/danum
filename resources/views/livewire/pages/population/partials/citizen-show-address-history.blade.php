<x-ui.card>
    <x-slot:header>
        <h2 class="font-semibold text-slate-900">Riwayat Alamat</h2>
    </x-slot:header>

    <div class="divide-y divide-slate-100 -mx-6 -my-6">
        @forelse($citizen->addresses as $address)
            <div class="px-6 py-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ ucfirst($address->jenis_alamat) }}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $address->alamat ?: '-' }}{{ $address->rt || $address->rw ? ' • RT '.$address->rt.'/RW '.$address->rw : '' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ collect([$address->kelurahan, $address->kecamatan, $address->kabupaten_kota, $address->provinsi, $address->kode_pos])->filter()->join(', ') }}
                        </p>
                    </div>
                    <span class="text-xs text-slate-500">Mulai {{ $address->berlaku_mulai?->format('d/m/Y') ?: '-' }}</span>
                </div>
            </div>
        @empty
            <div class="px-6 py-10 text-center text-sm text-slate-500">Belum ada riwayat alamat.</div>
        @endforelse
    </div>
</x-ui.card>
