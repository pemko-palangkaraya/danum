<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-semibold tracking-tight text-slate-900">Outgoing Letters</h1><p class="mt-1 text-sm text-slate-500">{{ $isSuperAdmin ? 'Arsip seluruh surat keluar tenant dan pemulihan surat yang dihapus.' : 'Buat, lihat, validasi, terbitkan, dan verifikasi surat keluar.' }}</p></div>
        @unless($isSuperAdmin)<button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Create Letter</button>@endunless
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input wire:model.live="search" type="search" placeholder="Cari nomor, penerima, perihal..." class="form-control sm:max-w-md">
            <select wire:model.live="filter" class="form-select sm:w-44">
                <option value="all">{{ $isSuperAdmin ? 'Active Letters' : 'All' }}</option><option value="draft">Draft</option><option value="validated">Validated</option><option value="issued">Issued</option><option value="cancelled">Cancelled</option>
                @if($isSuperAdmin)<option value="deleted">Deleted</option>@endif
            </select>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($letters as $letter)
                <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between {{ $letter->trashed() ? 'bg-rose-50/50' : '' }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-semibold text-slate-400">{{ $letter->number }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $letter->status->value }}</span>@if($letter->trashed())<span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Deleted</span>@endif</div>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ $letter->subject }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $letter->recipient_name }} · {{ $letter->letterType?->name }}@if($isSuperAdmin && $letter->tenant) · {{ $letter->tenant->name }}@endif</p>
                        @if($letter->signer_name)<p class="mt-1 text-xs text-slate-500">Penanda tangan: <span class="font-medium text-slate-700">{{ $letter->signer_name }}</span> · {{ $letter->signer_title }}</p>@endif
                        @if($letter->validator_name)<p class="mt-1 text-xs text-slate-500">Validator: <span class="font-medium text-slate-700">{{ $letter->validator_name }}</span> · {{ $letter->validator_title }}</p>@endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if(! $letter->trashed())
                            <a href="{{ route('outgoing-letters.show', $letter->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Preview</a>
                            @unless($isSuperAdmin)
                                @if($letter->status->value === 'draft' && (int) $letter->validator_user_id === (int) auth()->id())<button wire:click="validateLetter('{{ $letter->id }}')" class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">Validate</button>@endif
                                @if($letter->status->value === 'validated' && (int) $letter->signer_user_id === (int) auth()->id())<button wire:click="issue('{{ $letter->id }}')" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Issue</button>@endif
                            @endunless
                            @if($letter->status->value === 'issued')<a href="{{ route('verification.show', $letter->verification_token) }}" target="_blank" class="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Verify</a>@endif
                        @elseif($isSuperAdmin)
                            <button wire:click="restoreLetter('{{ $letter->id }}')" wire:confirm="Restore surat ini?" class="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Restore</button>
                        @endif
                    </div>
                </div>
            @empty<div class="p-12 text-center text-sm text-slate-500">{{ $isSuperAdmin && $filter === 'deleted' ? 'Tidak ada surat yang dihapus.' : 'Belum ada surat keluar.' }}</div>@endforelse
        </div><div class="border-t border-slate-100 p-4">{{ $letters->links() }}</div>
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">Create Outgoing Letter</h2><p class="mt-1 text-sm text-slate-500">Pilih jenis surat, validator, dan pejabat penanda tangan. Field dibuat otomatis dari placeholder template.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Letter Type</label><select wire:model.live="letter_type_id" class="form-select mt-1"><option value="">Pilih jenis surat</option>@foreach($letterTypes as $type)<option value="{{ $type->id }}">{{ $type->code }} — {{ $type->name }}</option>@endforeach</select>@error('letter_type_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Validator</label><select wire:model="validator_position_id" class="form-select mt-1"><option value="">Pilih validator</option>@foreach($validatorPositions as $position)<option value="{{ $position->id }}">{{ $position->name }} — {{ $position->holders->first()?->user?->name ?? 'Belum ditetapkan' }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-400">Hanya jabatan yang diizinkan memverifikasi dan memiliki pejabat aktif.</p>@error('validator_position_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                        <div class="sm:col-span-2"><label class="text-sm font-medium text-slate-700">Penanda Tangan</label><select wire:model="signer_position_id" class="form-select mt-1"><option value="">Pilih pejabat penanda tangan</option>@foreach($signerPositions as $position)<option value="{{ $position->id }}">{{ $position->name }} — {{ $position->holders->first()?->user?->name ?? 'Belum ditetapkan' }}</option>@endforeach</select><p class="mt-1 text-xs text-slate-400">Hanya jabatan yang diizinkan TTE dan memiliki pejabat aktif yang tersedia.</p>@error('signer_position_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    </div>
                    @if($variables)
                        <div class="rounded-xl border border-slate-200 bg-white p-4"><div><h3 class="text-sm font-semibold text-slate-900">Data Surat</h3><p class="mt-1 text-xs text-slate-500">Identitas validator dan penanda tangan dikelola sistem dari jabatan yang dipilih.</p></div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach($variables as $variable)
                                    @php
                                        $label = $variableLabels[$variable] ?? ucwords(str_replace('_',' ',$variable));
                                        $systemVariable = in_array($variable, ['letterhead','tenant_name','tenant_city','tenant_district','tenant_village','tenant_province','tenant_address','tenant_phone','tenant_email','tenant_head_name','tenant_head_title','tte'], true);
                                        $wide = in_array($variable, ['recipient_address','subject','tenant_address'], true);
                                        $dateVariable = (bool) preg_match('/(^|_)date$/i', $variable);
                                        $birthDateVariable = (bool) preg_match('/(^|_)birth_date$/i', $variable);
                                    @endphp
                                    @if(! $systemVariable)
                                        <div class="{{ $wide ? 'sm:col-span-2' : '' }}"><label class="text-sm font-medium text-slate-700">{{ $label }}</label>
                                            @if($dateVariable)<input type="date" wire:model="variableValues.{{ $variable }}" class="form-control mt-1" /><p class="mt-1 text-xs text-slate-400">@if($birthDateVariable)Tanggal lahir tidak boleh hari ini atau masa depan.@elseTanggal surat boleh backdate atau future, tetapi tidak boleh tanggal hari ini.@endif</p>
                                            @elseif($variable === 'recipient_address')<textarea wire:model="variableValues.{{ $variable }}" rows="2" class="form-textarea mt-1"></textarea>
                                            @else<input wire:model="variableValues.{{ $variable }}" class="form-control mt-1">@endif
                                            @error('variableValues.'.$variable)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($errors->any())<div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Create Draft</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
