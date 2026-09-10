<div class="relative">
    <input
        wire:model.live.debounce.300ms="search"
        class="form-control mt-1"
        inputmode="numeric"
        maxlength="16"
        autocomplete="off"
        placeholder="Ketik minimal 3 digit NIK"
    >

    @if($showSuggestions && strlen($search) >= 3 && $suggestions->isNotEmpty())
        <div class="absolute left-0 right-0 top-full z-30 mt-1 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
            @foreach($suggestions as $citizen)
                <button
                    type="button"
                    wire:click="selectCitizen(@js($citizen->nik))"
                    class="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50"
                >
                    <span class="block text-sm font-semibold text-slate-800">{{ $citizen->nama_lengkap }}</span>
                    <span class="mt-0.5 block text-xs text-slate-500">NIK: {{ $citizen->nik }}</span>
                </button>
            @endforeach
        </div>
    @elseif($showSuggestions && strlen($search) >= 3 && strlen($search) < 16)
        <p class="mt-1 text-xs text-slate-400">Ketik lebih banyak digit untuk mempersempit pencarian.</p>
    @elseif($showSuggestions && strlen($search) === 16 && $suggestions->isEmpty())
        <p class="mt-1 text-xs text-amber-600">NIK belum ditemukan. Periksa kembali nomor yang dimasukkan.</p>
    @endif
</div>
