<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Kop Surat</h2>
            <p class="mt-1 text-xs text-slate-500">Atur kop secara terstruktur. Template DOCX cukup menggunakan marker <code>&#123;&#123;letterhead&#125;&#125;</code>.</p>
        </div>
        <button type="button" wire:click="previewLetterhead" wire:loading.attr="disabled" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60">
            <span wire:loading.remove wire:target="previewLetterhead">Preview Kop</span>
            <span wire:loading wire:target="previewLetterhead">Menyiapkan...</span>
        </button>
    </div>

    <div class="space-y-6 p-5 sm:p-6">
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <h3 class="text-sm font-semibold text-slate-800">Isi Kop Surat</h3>
            <p class="mt-1 text-xs leading-5 text-slate-600">Isi teks pada kotak putih di bawah. Ukuran tulisan dipilih pada kotak <strong>Ukuran</strong> di sebelah kanan setiap baris.</p>
        </div>

        <div class="space-y-4">
            @foreach ([
                ['letterheadLine1', 'letterhead-line1', 'Baris 1', 'PEMERINTAH KOTA PALANGKA RAYA', 'letterheadLine1Size', 'letterhead-line1-size'],
                ['letterheadLine2', 'letterhead-line2', 'Baris 2', 'KECAMATAN BUKIT BATU', 'letterheadLine2Size', 'letterhead-line2-size'],
                ['letterheadLine3', 'letterhead-line3', 'Baris 3 — Kelurahan / Instansi', 'KELURAHAN TANGKILING', 'letterheadLine3Size', 'letterhead-line3-size'],
            ] as [$property, $id, $label, $placeholder, $sizeProperty, $sizeId])
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_150px] sm:items-end">
                    <x-ui.field label="{{ $label }}" for="{{ $id }}" error="{{ $errors->first($property) }}">
                        <input id="{{ $id }}" type="text" wire:model="{{ $property }}" @disabled(!$canUpdate) maxlength="150" placeholder="{{ $placeholder }}" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                    </x-ui.field>
                    <x-ui.field label="Ukuran" for="{{ $sizeId }}" error="{{ $errors->first($sizeProperty) }}">
                        <select id="{{ $sizeId }}" wire:model="{{ $sizeProperty }}" @disabled(!$canUpdate) class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                            @foreach ([10,11,12,13,14,15,16,17,18,20,22,24,26,28,30,32,36] as $size)
                                <option value="{{ $size }}">{{ $size }} pt</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </div>
            @endforeach

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <x-ui.field label="Alamat Lengkap" for="letterhead-address" error="{{ $errors->first('address') }}">
                    <textarea id="letterhead-address" wire:model="address" @disabled(!$canUpdate) rows="6" placeholder="Jl. Contoh No. 1, Kelurahan ..., Kecamatan ...&#10;Kode Pos 73111&#10;Telp. (0536) 123456&#10;Email: kantor@contoh.go.id&#10;Website: https://contoh.go.id" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"></textarea>
                    <p class="mt-2 text-xs leading-5 text-slate-500">Tulis seluruh informasi alamat dan kontak secara manual di sini, termasuk kode pos, telepon, email, dan website. Tekan Enter untuk membuat baris baru. Susunan ini akan dipertahankan pada preview dan DOCX.</p>
                </x-ui.field>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Logo / Lambang</h3>
                <p class="mt-1 text-xs text-slate-500">PNG transparan disarankan. Logo akan ditempatkan di sisi kiri kop.</p>
            </div>
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_240px]">
                @if ($canUpdate)
                <x-ui.field label="Ganti Logo" for="tenant-logo" error="{{ $errors->first('logo') }}">
                    <input id="tenant-logo" type="file" wire:model="logo" accept="image/png,image/jpeg" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:border-0 file:border-r file:border-slate-300 file:bg-slate-50 file:px-4 file:py-2.5 file:font-semibold file:text-slate-700 hover:file:bg-slate-100">
                    <p class="mt-1.5 text-xs text-slate-500">PNG atau JPG/JPEG. Maksimal 2 MB.</p>
                    <div wire:loading wire:target="logo" class="mt-2 text-xs text-slate-500">Uploading...</div>
                </x-ui.field>
                @endif
                <div>
                    <p class="text-sm font-medium text-slate-700">Logo aktif</p>
                    <div class="mt-2 flex h-36 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4">
                        @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="Preview logo baru" class="max-h-28 max-w-32 object-contain">
                        @elseif ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo organisasi" class="max-h-28 max-w-32 object-contain">
                        @else
                        <span class="text-xs text-slate-400">Belum ada logo</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-800">
            <strong>Marker template:</strong> letakkan <code>&#123;&#123;letterhead&#125;&#125;</code> pada posisi kop. Saat surat dibuat, DANUM membentuk kop berdasarkan pengaturan organisasi ini.
        </div>
    </div>

    @if ($showLetterheadPreview)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-3 sm:p-6" wire:keydown.escape="closeLetterheadPreview">
        <div class="flex max-h-[95vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-slate-100 shadow-2xl" role="dialog" aria-modal="true" aria-label="Preview kop surat">
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-900">Preview Kop Surat</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Preview mengikuti susunan yang digunakan saat DOCX dibuat: logo kiri, teks kop kanan, garis bawah penuh.</p>
                </div>
                <button type="button" wire:click="closeLetterheadPreview" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">Tutup</button>
            </div>

            <div class="overflow-auto p-4 sm:p-8">
                <div class="mx-auto w-full max-w-[794px] bg-white px-[42px] py-[42px] shadow-lg sm:px-[55px] sm:py-[45px]">
                    <div class="border-b-[3px] border-slate-800 pb-3">
                        <div class="flex items-center gap-4">
                            <div class="flex w-[92px] shrink-0 items-center justify-center">
                                @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="Logo" class="max-h-[78px] max-w-[78px] object-contain">
                                @elseif ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="max-h-[78px] max-w-[78px] object-contain">
                                @endif
                            </div>
                            <div class="min-w-0 flex-1 text-center font-[Arial,sans-serif] text-slate-900">
                                @foreach ([[$letterheadLine1, $letterheadLine1Size], [$letterheadLine2, $letterheadLine2Size], [$letterheadLine3, $letterheadLine3Size]] as [$line, $size])
                                @if (trim($line) !== '')
                                <div class="font-bold uppercase leading-[1.05]" style="font-size: {{ $size }}pt">{{ $line }}</div>
                                @endif
                                @endforeach

                                @if (trim($address) !== '')
                                <div class="mt-1 leading-[1.15]" style="font-size: {{ $letterheadMetaSize }}pt">
                                    @foreach (preg_split('/\R/u', trim($address)) ?: [] as $addressLine)
                                    @if (trim($addressLine) !== '')
                                    <div>{{ trim($addressLine) }}</div>
                                    @endif
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pt-6 text-center text-slate-400">
                        <div class="mx-auto max-w-xl text-[10px] uppercase tracking-widest">Area isi surat dimulai di sini</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>
