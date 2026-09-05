<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
    <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-xl">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Draft Surat' : 'Create Outgoing Letter' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $editingId ? 'Periksa dan perbarui data sebelum dikirim untuk verifikasi.' : 'Isi data surat, lalu gunakan Preview untuk pemeriksaan akhir sebelum Submit.' }}</p>
        </div>

        <form wire:submit="save" class="space-y-5 p-6">
            <div class="grid gap-4">
                <div>
                    <label class="text-sm font-medium text-slate-700">Jenis Surat</label>
                    <select wire:model.live="letter_type_id" class="form-select mt-1">
                        <option value="">Pilih jenis surat</option>
                        @foreach($letterTypes as $type)<option value="{{ $type->id }}">{{ $type->code }} — {{ $type->name }}</option>@endforeach
                    </select>
                    @error('letter_type_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach([['validator_position_id', 'Verifikator', $validatorPositions, 'Belum ada verifikator tersedia', 'Pilih verifikator'], ['signer_position_id', 'Penanda Tangan', $signerPositions, 'Pilih pejabat penanda tangan', 'Pilih pejabat penanda tangan']] as [$field, $label, $positions, $empty, $placeholder])
                        <div>
                            <label class="text-sm font-medium text-slate-700">{{ $label }}</label>
                            <select wire:model="{{ $field }}" class="form-select mt-1">
                                <option value="">{{ $positions->isEmpty() ? $empty : $placeholder }}</option>
                                @foreach($positions as $position)<option value="{{ $position->id }}">{{ $position->name }} — {{ $position->holders->first()?->user?->name ?? 'Belum ditetapkan' }}</option>@endforeach
                            </select>
                            @error($field)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </div>

            @if($variables)
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <h3 class="text-sm font-semibold text-slate-900">Data Surat</h3>
                    <p class="mt-1 text-xs text-slate-500">Data warga dan data yang dihitung sistem diambil otomatis dari data kependudukan.</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        @foreach($variables as $variable)
                            @php
                                $definition = is_string($variable) ? \App\Support\LetterVariableSchema::parseRepeater($variable) : null;
                                $label = $variableLabels[$variable] ?? ucwords(str_replace('_', ' ', (string) $variable));
                                $readOnly = $this->isReadOnlyVariable((string) $variable);
                                $wide = in_array($variable, ['recipient_address','subject','tenant_address'], true);
                                $dateVariable = $variable === 'tanggal_meninggal' || (bool) preg_match('/(^|_)date$/i', (string) $variable);
                            @endphp
                            @if(! $readOnly && ! $definition)
                                <div class="{{ $wide ? 'sm:col-span-2' : '' }}">
                                    <label class="text-sm font-medium text-slate-700">{{ $label }}</label>
                                    @if($dateVariable && $this->citizen_id && $variable === 'tanggal_meninggal')
                                        <input type="text" wire:model.blur="variableValues.{{ $variable }}" class="form-control mt-1" placeholder="dd mmm yyyy, contoh: 06 Sep 2026" inputmode="numeric" autocomplete="off">
                                        <p class="mt-1 text-xs text-slate-400">Gunakan format tanggal: dd mmm yyyy, misalnya 06 Sep 2026.</p>
                                    @elseif($dateVariable)
                                        <input type="date" max="{{ now()->toDateString() }}" wire:model="variableValues.{{ $variable }}" class="form-control mt-1">
                                    @elseif($variable === 'recipient_address')
                                        <textarea wire:model="variableValues.{{ $variable }}" rows="2" class="form-textarea mt-1"></textarea>
                                    @else
                                        <input wire:model="variableValues.{{ $variable }}" class="form-control mt-1">
                                    @endif
                                    @error('variableValues.'.$variable)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @elseif($readOnly && ! $definition && $this->citizen_id)
                                <div class="{{ $wide ? 'sm:col-span-2' : '' }}">
                                    <label class="text-sm font-medium text-slate-700">{{ $label }}</label>
                                    <input value="{{ $dateVariable ? $this->formatIndonesianDate($variableValues[$variable] ?? '') : ($variableValues[$variable] ?? '') }}" readonly class="form-control mt-1 bg-slate-50 text-slate-600">
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach($repeaters as $repeater)
                @php
                    $autoFilledRepeater = $this->citizen_id && $repeater['key'] === 'anak_ditinggalkan';
                @endphp
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-indigo-900">{{ $repeater['label'] }}</h3>
                            <p class="mt-1 text-xs text-indigo-700">{{ $autoFilledRepeater ? 'Data anak diambil otomatis dari anggota aktif KK warga.' : 'Tambahkan satu atau beberapa data. Setiap baris akan diulang otomatis pada template DOCX.' }}</p>
                        </div>
                        @if(! $autoFilledRepeater)
                            <button type="button" wire:click="addRepeaterRow('{{ $repeater['key'] }}')" class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">+ Tambah</button>
                        @endif
                    </div>
                    <div class="mt-4 space-y-3">
                        @foreach(($variableValues[$repeater['key']] ?? []) as $rowIndex => $row)
                            <div wire:key="repeater-{{ $repeater['key'] }}-{{ $rowIndex }}" class="rounded-xl border border-indigo-100 bg-white p-4">
                                <div class="mb-3 flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Data {{ $rowIndex + 1 }}</span>
                                    @if(! $autoFilledRepeater && count($variableValues[$repeater['key']] ?? []) > 1)
                                        <button type="button" wire:click="removeRepeaterRow('{{ $repeater['key'] }}', {{ $rowIndex }})" class="text-xs font-medium text-rose-600 hover:text-rose-700">Hapus</button>
                                    @endif
                                </div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach($repeater['fields'] as $field)
                                        <div>
                                            <label class="text-sm font-medium text-slate-700">{{ $field['label'] }}</label>
                                            <input wire:model="variableValues.{{ $repeater['key'] }}.{{ $rowIndex }}.{{ $field['key'] }}" class="form-control mt-1 {{ $autoFilledRepeater ? 'bg-slate-50 text-slate-600' : '' }}" placeholder="{{ $field['label'] }}" @readonly($autoFilledRepeater)>
                                            @error('variableValues.'.$repeater['key'].'.'.$rowIndex.'.'.$field['key'])<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="flex items-center justify-between gap-2 border-t border-slate-100 pt-5">
                <span class="text-xs text-slate-400">Setelah Submit, draft terkunci sampai diverifikasi atau ditolak.</span>
                <div class="flex gap-2"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">{{ $editingId ? 'Simpan Perubahan' : 'Simpan Draft' }}</button></div>
            </div>
        </form>
    </div>
</div>