<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-slate-900">Kop Surat</h2>
        <p class="mt-1 text-xs text-slate-500">Atur isi kop surat secara terstruktur. Template DOCX cukup menggunakan marker <code>&#123;&#123;letterhead&#125;&#125;</code>.</p>
    </div>

    <div class="space-y-6 p-5 sm:p-6">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-sm font-semibold text-slate-800">Isi Kop Surat</h3>
            <p class="mt-1 text-xs text-slate-500">Baris dapat dikosongkan jika tidak diperlukan. Pengaturan ini akan dirender langsung ke DOCX/PDF.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-ui.field label="Baris 1" for="letterhead-line1" error="{{ $errors->first('letterheadLine1') }}">
                <input id="letterhead-line1" type="text" wire:model="letterheadLine1" @disabled(!$canUpdate) maxlength="150" placeholder="mis. PEMERINTAH KOTA PALANGKA RAYA" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
            </x-ui.field>

            <x-ui.field label="Baris 2" for="letterhead-line2" error="{{ $errors->first('letterheadLine2') }}">
                <input id="letterhead-line2" type="text" wire:model="letterheadLine2" @disabled(!$canUpdate) maxlength="150" placeholder="mis. KECAMATAN BUKIT BATU" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
            </x-ui.field>

            <x-ui.field label="Baris 3 — Kelurahan / Instansi" for="letterhead-line3" error="{{ $errors->first('letterheadLine3') }}">
                <input id="letterhead-line3" type="text" wire:model="letterheadLine3" @disabled(!$canUpdate) maxlength="150" placeholder="mis. KELURAHAN TANGKILING" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
            </x-ui.field>

            <x-ui.field label="Kode Pos" for="postal-code" error="{{ $errors->first('postalCode') }}">
                <input id="postal-code" type="text" wire:model="postalCode" @disabled(!$canUpdate) maxlength="10" inputmode="numeric" placeholder="mis. 73222" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
            </x-ui.field>

            <div class="sm:col-span-2">
                <x-ui.field label="Alamat" for="letterhead-address" error="{{ $errors->first('address') }}">
                    <textarea id="letterhead-address" wire:model="address" @disabled(!$canUpdate) rows="2" placeholder="Alamat kantor" class="form-textarea w-full disabled:bg-slate-50 disabled:text-slate-500"></textarea>
                </x-ui.field>
            </div>

            <x-ui.field label="Website (opsional)" for="letterhead-website" error="{{ $errors->first('website') }}">
                <input id="letterhead-website" type="url" wire:model="website" @disabled(!$canUpdate) maxlength="255" placeholder="https://contoh.go.id" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
            </x-ui.field>

            <x-ui.field label="Email (opsional)" for="letterhead-email" error="{{ $errors->first('email') }}">
                <input id="letterhead-email" type="email" wire:model="email" @disabled(!$canUpdate) maxlength="150" placeholder="kantor@contoh.go.id" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
            </x-ui.field>
        </div>

        <div class="border-t border-slate-200 pt-6">
            <h3 class="text-sm font-semibold text-slate-800">Logo / Lambang</h3>
            <p class="mt-1 text-xs text-slate-500">PNG transparan disarankan. Logo akan ditempatkan di sisi kiri kop saat surat dirender.</p>

            <div class="mt-4 grid gap-5 lg:grid-cols-[minmax(0,1fr)_280px]">
                @if ($canUpdate)
                    <x-ui.field label="Ganti Logo" for="tenant-logo" error="{{ $errors->first('logo') }}">
                        <input id="tenant-logo" type="file" wire:model="logo" accept="image/png,image/jpeg" class="form-input w-full">
                        <p class="mt-1.5 text-xs text-slate-500">PNG atau JPG/JPEG. Maksimal 2 MB.</p>
                        <div wire:loading wire:target="logo" class="mt-2 text-xs text-slate-500">Uploading...</div>
                    </x-ui.field>
                @endif

                <div>
                    <p class="text-sm font-medium text-slate-700">Preview Logo</p>
                    <div class="mt-2 flex h-40 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white p-4">
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

        <div class="border-t border-slate-200 pt-6">
            <h3 class="text-sm font-semibold text-slate-800">Kepala Organisasi</h3>
            <p class="mt-1 text-xs text-slate-500">Data pimpinan dapat digunakan sebagai default penandatangan surat.</p>

            <div class="mt-4 grid gap-5 sm:grid-cols-2">
                <x-ui.field label="Jabatan Kepala" for="letterhead-head-title" error="{{ $errors->first('headTitle') }}">
                    <input id="letterhead-head-title" type="text" wire:model="headTitle" @disabled(!$canUpdate) maxlength="100" placeholder="mis. LURAH TANGKILING" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
                </x-ui.field>

                <x-ui.field label="Nama Kepala" for="letterhead-head-name" error="{{ $errors->first('headName') }}">
                    <input id="letterhead-head-name" type="text" wire:model="headName" @disabled(!$canUpdate) maxlength="150" placeholder="Nama lengkap" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
                </x-ui.field>

                <x-ui.field label="NIP Kepala" for="letterhead-head-nip" error="{{ $errors->first('headNip') }}">
                    <input id="letterhead-head-nip" type="text" wire:model="headNip" @disabled(!$canUpdate) maxlength="30" inputmode="numeric" placeholder="NIP" class="form-input w-full disabled:bg-slate-50 disabled:text-slate-500">
                </x-ui.field>
            </div>
        </div>

        <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-800">
            Template surat tidak perlu menyimpan gambar kop. Cukup letakkan <code>&#123;&#123;letterhead&#125;&#125;</code> pada posisi kop. DANUM akan membentuk kop dari pengaturan organisasi saat DOCX/PDF dibuat.
        </div>
    </div>
</section>
