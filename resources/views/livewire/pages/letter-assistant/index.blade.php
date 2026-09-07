<?php

declare(strict_types=1);

use App\Services\LetterRecommendationService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $input = '';
    public array $recommendations = [];
    public bool $searched = false;

    public function recommend(LetterRecommendationService $service): void
    {
        $this->validate(['input' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->recommendations = $service->recommend($this->input, auth()->user()->tenant_id);
        $this->searched = true;
    }

    public function clear(): void
    {
        $this->input = '';
        $this->recommendations = [];
        $this->searched = false;
        $this->resetValidation();
    }
};
?>

<div class="mx-auto max-w-5xl space-y-6">
    <div>
        <p class="text-sm font-semibold text-indigo-600">DANUM Assistant</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Ceritakan kebutuhan Anda</h1>
        <p class="mt-2 max-w-2xl text-sm text-slate-500">Tidak perlu mencari kode klasifikasi. Jelaskan pekerjaan administratif dengan bahasa sehari-hari, lalu DANUM mencocokkannya dengan jenis naskah dan klasifikasi yang tersedia.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <form wire:submit="recommend" class="space-y-4">
            <label for="letter-assistant-input" class="text-sm font-semibold text-slate-800">Apa yang ingin Anda buat?</label>
            <textarea id="letter-assistant-input" wire:model="input" rows="4" autofocus class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-50" placeholder="Contoh: Saya mau bikin surat minta data penduduk ke Dukcapil untuk kebutuhan perencanaan."></textarea>
            @error('input')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50">Cari rekomendasi</button>
                @if($input !== '')<button type="button" wire:click="clear" class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Bersihkan</button>@endif
            </div>
        </form>
    </div>

    @if($searched)
        @if(count($recommendations))
            <div class="space-y-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Rekomendasi DANUM</h2>
                    <p class="mt-1 text-sm text-slate-500">Hasil ini berasal dari pencocokan kata dan sinonim, bukan dari LLM.</p>
                </div>
                @foreach($recommendations as $recommendation)
                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Jenis Naskah</p>
                                <h3 class="mt-1 text-base font-semibold text-slate-900">{{ $recommendation['letter_type']?->name ?? 'Belum ada jenis naskah yang terhubung' }}</h3>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-400">Klasifikasi</p><p class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $recommendation['classification']->code }}</p><p class="mt-1 text-sm text-slate-600">{{ $recommendation['classification']->name }}</p></div>
                                    <div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-400">Dasar / Sumber</p><p class="mt-1 text-sm font-medium text-slate-700">{{ $recommendation['classification']->source ?: 'Belum dicatat' }}</p></div>
                                </div>
                            </div>
                            <div class="shrink-0 rounded-xl bg-indigo-50 px-3 py-2 text-center"><p class="text-xs text-indigo-500">Kecocokan</p><p class="text-lg font-bold text-indigo-700">{{ number_format($recommendation['confidence'] * 100, 0) }}%</p></div>
                        </div>
                        @if(count($recommendation['matched_terms']))<p class="mt-4 text-xs text-slate-400">Kata yang cocok: {{ implode(', ', $recommendation['matched_terms']) }}</p>@endif
                        @if($recommendation['letter_type'])<a href="{{ route('outgoing-letters.index') }}" class="mt-4 inline-flex rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Gunakan untuk membuat surat</a>@endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><h2 class="font-semibold text-amber-900">Belum menemukan kecocokan</h2><p class="mt-1 text-sm text-amber-800">Coba jelaskan tujuan, objek, atau tindakan yang ingin dilakukan. Contoh: “undang kepala OPD untuk rapat koordinasi” atau “minta data penduduk untuk perencanaan”.</p></div>
        @endif
    @endif

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
        <h2 class="text-sm font-semibold text-slate-800">Prinsip kerja</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            <div><p class="text-sm font-medium text-slate-700">1. Ceritakan</p><p class="mt-1 text-xs text-slate-500">Gunakan bahasa yang biasa Anda gunakan.</p></div>
            <div><p class="text-sm font-medium text-slate-700">2. DANUM mencocokkan</p><p class="mt-1 text-xs text-slate-500">Sistem mencari jenis naskah dan klasifikasi yang tersedia.</p></div>
            <div><p class="text-sm font-medium text-slate-700">3. Anda mengonfirmasi</p><p class="mt-1 text-xs text-slate-500">Rekomendasi tidak mengubah aturan resmi secara otomatis.</p></div>
        </div>
    </div>
</div>
