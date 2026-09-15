<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-900">Lampiran</h2>
            <p class="mt-1 text-xs text-slate-500">Tambahkan lampiran PDF. DANUM akan memberi header lampiran, menghitung jumlah halaman, lalu menggabungkannya ke PDF final.</p>
        </div>
        @if($totalPages > 0)
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">{{ $totalPages }} {{ $totalPages === 1 ? 'lembar' : 'lembar' }}</span>
        @endif
    </div>

    @if($attachments->isNotEmpty())
        <div class="mt-4 space-y-2">
            @foreach($attachments as $attachment)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">{{ $attachment->sequence }}</span>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-semibold text-slate-800">Lampiran {{ $attachment->sequence }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $attachment->title }} · {{ $attachment->page_count }} {{ $attachment->page_count === 1 ? 'halaman' : 'halaman' }}</div>
                            </div>
                        </div>
                    </div>
                    @if($editable)
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="move('{{ $attachment->id }}', -1)" @disabled($loop->first) class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-40">↑</button>
                            <button type="button" wire:click="move('{{ $attachment->id }}', 1)" @disabled($loop->last) class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-600 disabled:cursor-not-allowed disabled:opacity-40">↓</button>
                            <button type="button" wire:click="remove('{{ $attachment->id }}')" wire:confirm="Hapus lampiran ini?" class="rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="mt-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">Belum ada lampiran.</div>
    @endif

    @if($editable)
        <div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50 p-4">
            <label class="text-sm font-semibold text-indigo-900">Tambah lampiran PDF</label>
            <input type="file" wire:model="attachmentFiles" multiple accept="application/pdf" class="form-control mt-2 bg-white">
            @error('attachmentFiles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('attachmentFiles.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

            @if(count($attachmentFiles))
                <div class="mt-3 space-y-2">
                    @foreach($attachmentFiles as $index => $file)
                        <div class="rounded-lg border border-indigo-100 bg-white p-3">
                            <div class="text-xs font-semibold text-slate-700">{{ $file->getClientOriginalName() }}</div>
                            <input wire:model="attachmentTitles.{{ $index }}" class="form-control mt-2" placeholder="Judul lampiran (opsional)">
                        </div>
                    @endforeach
                </div>
                <button type="button" wire:click="upload" wire:loading.attr="disabled" class="mt-3 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Simpan Lampiran</button>
            @endif
            <p class="mt-2 text-xs text-indigo-700">V1 menerima PDF maksimal 20 MB per file dan 20 lampiran per surat.</p>
        </div>
    @elseif($attachments->isNotEmpty())
        <p class="mt-4 text-xs text-slate-400">Lampiran terkunci karena surat sudah diajukan untuk verifikasi.</p>
    @endif
</section>
