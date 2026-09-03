@if(auth()->user()->hasPermission('population.manage'))
    <div class="border-t border-slate-100 bg-white px-6 py-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-800">Tambahkan Anggota</p>
                <p class="mt-0.5 text-xs text-slate-500">Cari warga berdasarkan nama atau NIK, lalu tentukan hubungan dalam keluarga.</p>
            </div>
            @if($memberSearch !== '')
                <span class="text-xs text-slate-400">{{ $memberCandidates->count() }} hasil</span>
            @endif
        </div>

        <div class="mt-3 grid gap-3 sm:grid-cols-[1fr_220px]">
            <div class="relative">
                <svg style="left: 1rem;" class="pointer-events-none absolute top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>
                </svg>
                <input wire:model.live.debounce.300ms="memberSearch" placeholder="Cari nama atau NIK..." style="padding-left: 2.75rem; padding-right: 2.5rem;" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 text-sm shadow-sm transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                @if($memberSearch !== '')
                    <button type="button" wire:click="$set('memberSearch', '')" aria-label="Bersihkan pencarian" class="absolute right-3 top-1/2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"></path></svg>
                    </button>
                @endif
            </div>

            <select wire:model="memberRelationship" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm shadow-sm transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200">
                <option value="">Pilih hubungan</option>
                <option value="Kepala Keluarga">Kepala Keluarga</option>
                <option value="Istri">Istri</option>
                <option value="Suami">Suami</option>
                <option value="Anak">Anak</option>
                <option value="Menantu">Menantu</option>
                <option value="Cucu">Cucu</option>
                <option value="Orang Tua">Orang Tua</option>
                <option value="Mertua">Mertua</option>
                <option value="Famili Lain">Famili Lain</option>
                <option value="Pembantu">Pembantu</option>
                <option value="Lainnya">Lainnya</option>
            </select>
        </div>

        @if($memberSearch !== '')
            <div class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                @forelse($memberCandidates as $citizen)
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3 last:border-0 hover:bg-slate-50">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-slate-800">{{ $citizen->nama_lengkap }}</div>
                            <div class="mt-0.5 font-mono text-xs text-slate-500">{{ $citizen->nik }}</div>
                        </div>
                        <button type="button" wire:click="addMember('{{ $detail->id }}', '{{ $citizen->id }}', '{{ $memberRelationship }}')" @disabled($memberRelationship === '') class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
                            Tambahkan
                        </button>
                    </div>
                @empty
                    <div class="px-4 py-7 text-center">
                        <p class="text-sm font-medium text-slate-700">Warga tidak ditemukan</p>
                        <p class="mt-1 text-xs text-slate-500">Coba gunakan nama lengkap atau NIK yang berbeda.</p>
                    </div>
                @endforelse
            </div>
        @else
            <p class="mt-2 text-xs text-slate-400">Ketik nama atau NIK untuk mencari warga yang akan ditambahkan.</p>
        @endif
    </div>
@endif
