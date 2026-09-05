@if($showForm)
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">{{ $editingId ? 'Edit Kartu Keluarga' : 'Tambah Kartu Keluarga' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Lengkapi identitas dan alamat keluarga.</p>
        </div>

        <form wire:submit="save">
            <div class="p-6">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium text-slate-700">No. KK</label>
                        <input wire:model="no_kk" inputmode="numeric" maxlength="16" autocomplete="off" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                        <p class="mt-1 text-xs text-slate-400">16 digit nomor Kartu Keluarga.</p>
                        @error('no_kk') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-1 lg:col-span-2">
                        <label class="text-sm font-medium text-slate-700">Kepala Keluarga <span class="font-normal text-slate-400">(opsional)</span></label>
                        <div class="relative mt-2">
                            <svg class="pointer-events-none absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>
                            </svg>
                            <input wire:model.live.debounce.300ms="headSearch" placeholder="Ketik nama atau NIK..." class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 pl-11 pr-16 text-sm shadow-sm focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                            @if($head_citizen_id)
                                <button type="button" wire:click="resetHead" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-400 hover:text-slate-700">Hapus</button>
                            @endif
                        </div>

                        @if($selectedHead && $headSearch !== '')
                            <div class="mt-2 flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-slate-800">{{ $selectedHead->nama_lengkap }}</div>
                                    <div class="mt-0.5 font-mono text-xs text-slate-500">{{ $selectedHead->nik }}</div>
                                </div>
                                <span class="shrink-0 rounded-full bg-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-600">Terpilih</span>
                            </div>
                        @endif

                        @if($headSearch !== '' && $headCitizens->count())
                            <div class="mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                @foreach($headCitizens as $citizen)
                                    <button type="button" wire:click="selectHead('{{ $citizen->id }}')" class="flex w-full items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 text-left transition last:border-0 hover:bg-slate-50">
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-semibold text-slate-800">{{ $citizen->nama_lengkap }}</div>
                                            <div class="mt-0.5 font-mono text-xs text-slate-500">{{ $citizen->nik }}</div>
                                        </div>
                                        <span class="shrink-0 text-xs font-semibold text-slate-400">Pilih</span>
                                    </button>
                                @endforeach
                            </div>
                        @elseif($headSearch !== '')
                            <p class="mt-2 text-xs text-slate-500">Warga tidak ditemukan.</p>
                        @endif
                        @error('head_citizen_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="text-sm font-medium text-slate-700">Alamat</label>
                        <textarea wire:model="alamat" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></textarea>
                        @error('alamat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @foreach([['rt','RT'],['rw','RW']] as [$field, $label])
                        <div>
                            <label class="text-sm font-medium text-slate-700">{{ $label }}</label>
                            <input wire:model="{{ $field }}" class="mt-2 w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">
                            @error($field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
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
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                <p class="text-xs text-slate-500">Pilihan wilayah mengikuti tenant yang sedang digunakan dan hanya menampilkan child tenant yang aktif.</p>
                <div class="flex gap-3">
                    <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
@endif
