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
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_150px] sm:items-end">
                    <x-ui.field label="Baris 1" for="letterhead-line1" error="{{ $errors->first('letterheadLine1') }}">
                        <input id="letterhead-line1" type="text" wire:model="letterheadLine1" @disabled(!$canUpdate) maxlength="150" placeholder="PEMERINTAH KOTA PALANGKA RAYA" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                    </x-ui.field>
                    <x-ui.field label="Ukuran" for="letterhead-line1-size" error="{{ $errors->first('letterheadLine1Size') }}">
                        <select id="letterhead-line1-size" wire:model="letterheadLine1Size" @disabled(!$canUpdate) class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                            @foreach ([10,11,12,13,14,15,16,17,18,20,22,24,26,28,30,32,36] as $size)
                                <option value="{{ $size }}">{{ $size }} pt</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_150px] sm:items-end">
                    <x-ui.field label="Baris 2" for="letterhead-line2" error="{{ $errors->first('letterheadLine2') }}">
                        <input id="letterhead-line2" type="text" wire:model="letterheadLine2" @disabled(!$canUpdate) maxlength="150" placeholder="KECAMATAN BUKIT BATU" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                    </x-ui.field>
                    <x-ui.field label="Ukuran" for="letterhead-line2-size" error="{{ $errors->first('letterheadLine2Size') }}">
                        <select id="letterhead-line2-size" wire:model="letterheadLine2Size" @disabled(!$canUpdate) class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                            @foreach ([10,11,12,13,14,15,16,17,18,20,22,24,26,28,30,32,36] as $size)
                                <option value="{{ $size }}">{{ $size }} pt</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_150px] sm:items-end">
                    <x-ui.field label="Baris 3 — Kelurahan / Instansi" for="letterhead-line3" error="{{ $errors->first('letterheadLine3') }}">
                        <input id="letterhead-line3" type="text" wire:model="letterheadLine3" @disabled(!$canUpdate) maxlength="150" placeholder="KELURAHAN TANGKILING" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                    </x-ui.field>
                    <x-ui.field label="Ukuran" for="letterhead-line3-size" error="{{ $errors->first('letterheadLine3Size') }}">
                        <select id="letterhead-line3-size" wire:model="letterheadLine3Size" @disabled(!$canUpdate) class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
                            @foreach ([10,11,12,13,14,15,16,17,18,20,22,24,26,28,30,32,36] as $size)
                                <option value="{{ $size }}">{{ $size }} pt</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_150px] sm:items-end">
                    <x-ui.field label="Alamat" for="letterhead-address" error="{{ $errors->first('address') }}">
                        <textarea id="letterhead-address" wire:model="address" @disabled(!$canUpdate) rows="2" placeholder="Alamat kantor" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"></textarea>
                    </x-ui.field>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5">
                        <label for="postal-code" class="block text-xs font-semibold text-slate-600">Kode Pos</label>
                        <input id="postal-code" type="text" wire:model="postalCode" @disabled(!$canUpdate) maxlength="10" inputmode="numeric" placeholder="73222" class="mt-1 block w-full border-0 bg-transparent p-0 text-sm font-semibold text-slate-900 outline-none ring-0 placeholder:text-slate-400 focus:ring-0 disabled:text-slate-500">
                        @error('postalCode') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field label="Website (opsional)" for="letterhead-website" error="{{ $errors->first('website') }}">
                <input id="letterhead-website" type="url" wire:model="website" @disabled(!$canUpdate) maxlength="255" placeholder="https://contoh.go.id" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
            </x-ui.field>
            <x-ui.field label="Email (opsional)" for="letterhead-email" error="{{ $errors->first('email') }}">
                <input id="letterhead-email" type="email" wire:model="email" @disabled(!$canUpdate) maxlength="150" placeholder="kantor@contoh.go.id" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500">
            </x-ui.field>
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-slate-800">Logo / Lambang</h3>
                <p class="mt-1 text-xs text-slate-500">PNG transparan disarankan. Logo akan ditempatkan di sisi kiri kop.</p>
            </div>
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_240px]">
                @if ($canUpdate)
                <x-ui.field label="Ganti Logo" for="tenant-logo" error="{{ $errors->first('logo') }}">
                    <input id="tenant-logo" type="file" wire:model="logo" accept="image/png,image/jpeg" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:border-0 file:border-r file:border-slate-300 file:bg-slate-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-100">
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
                    <p class="mt-0.5 text-xs text-slate-500">Preview menggunakan pengaturan yang sedang tampil, termasuk perubahan yang belum disimpan.</p>
                </div>
                <button type="button" wire:click="closeLetterheadPreview" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700">Tutup</button>
            </div>

            <div class="overflow-auto p-4 sm:p-8">
                <div class="mx-auto min-h-[700px] w-full max-w-[794px] bg-white px-[45px] py-[45px] shadow-lg sm:px-[65px]">
                    <div class="border-b-[3px] border-slate-800 pb-4">
                        <div class="grid grid-cols-[82px_minmax(0,1fr)] items-center gap-4">
                            <div class="flex justify-center">
                                @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" alt="Logo" class="max-h-[72px] max-w-[72px] object-contain">
                                @elseif ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="max-h-[72px] max-w-[72px] object-contain">
                                @endif
                            </div>
                            <div class="text-center font-[Arial,sans-serif] text-slate-900">
                                @foreach ([[$letterheadLine1, $letterheadLine1Size], [$letterheadLine2, $letterheadLine2Size], [$letterheadLine3, $letterheadLine3Size]] as [$line, $size])
                                @if (trim($line) !== '')
                                <div class="font-bold uppercase leading-tight" style="font-size: {{ $size }}pt">{{ $line }}</div>
                                @endif
                                @endforeach
                                @php($meta = array_values(array_filter([
                                trim($address) !== '' ? trim($address) : null,
                                trim($postalCode) !== '' ? 'Kode Pos ' . trim($postalCode) : null,
                                trim($phone) !== '' ? 'Telp. ' . trim($phone) : null,
                                trim($email) !== '' ? trim($email) : null,
                                trim($website) !== '' ? trim($website) : null,
                                ])))
                                @if ($meta !== [])
                                <div class="mt-1 leading-tight" style="font-size: {{ $letterheadMetaSize }}pt">{{ implode('  |  ', $meta) }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pt-8 text-center text-slate-400">
                        <div class="mx-auto max-w-xl border-b border-dashed border-slate-200 pb-3 text-[10px] uppercase tracking-widest">Area isi surat dimulai di sini</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</section>