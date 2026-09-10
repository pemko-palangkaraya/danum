<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-slate-900">Kop Surat</h2>
        <p class="mt-1 text-xs text-slate-500">Kop surat resmi yang digunakan pada PDF surat yang diterbitkan.</p>
    </div>

    <div class="p-5 sm:p-6">
        <div class="grid gap-5 lg:grid-cols-2">
            @if ($canUpdate)
                <x-ui.field label="Upload Kop Surat" for="tenant-letterhead" error="{{ $errors->first('letterhead') }}">
                    <input
                        id="tenant-letterhead"
                        type="file"
                        wire:model="letterhead"
                        accept="image/png,image/jpeg,image/webp"
                        class="form-input w-full"
                    />
                    <p class="mt-1.5 text-xs text-slate-500">PNG, JPG/JPEG, atau WEBP. Maksimal 4 MB.</p>
                    <div wire:loading wire:target="letterhead" class="mt-2 text-xs text-slate-500">Uploading...</div>
                </x-ui.field>
            @endif

            <div class="lg:col-span-1">
                <p class="text-sm font-medium text-slate-700">Preview Kop Aktif</p>
                <div class="mt-2 flex min-h-32 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    @if ($letterhead)
                        <img src="{{ $letterhead->temporaryUrl() }}" alt="Preview kop surat baru" class="max-h-40 max-w-full object-contain">
                    @elseif ($letterheadUrl)
                        <img src="{{ $letterheadUrl }}" alt="Kop surat saat ini" class="max-h-40 max-w-full object-contain">
                    @else
                        <div class="text-center text-xs text-slate-400">
                            <p>Belum ada kop surat.</p>
                            @if (! $canUpdate)
                                <p class="mt-1">Hubungi administrator tenant untuk memperbaruinya.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($canUpdate)
            <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
                Perubahan kop surat akan digunakan untuk surat yang diterbitkan setelah perubahan disimpan. Surat yang sudah terbit tidak diubah.
            </div>
        @endif
    </div>
</section>
