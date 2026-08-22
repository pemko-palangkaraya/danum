<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h1 class="text-2xl font-semibold tracking-tight text-slate-900">Outgoing Letters</h1><p class="mt-1 text-sm text-slate-500">Buat, lihat, validasi, terbitkan, dan verifikasi surat keluar.</p></div><button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Create Letter</button></div>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row"><input wire:model.live="search" type="search" placeholder="Cari nomor, penerima, perihal..." class="form-control sm:max-w-md"><select wire:model.live="filter" class="form-select sm:w-44"><option value="all">All</option><option value="draft">Draft</option><option value="validated">Validated</option><option value="issued">Issued</option><option value="cancelled">Cancelled</option></select></div>
        <div class="divide-y divide-slate-100">
            @forelse ($letters as $letter)
                <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-semibold text-slate-400">{{ $letter->number }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $letter->status->value }}</span></div><h2 class="mt-1 font-semibold text-slate-900">{{ $letter->subject }}</h2><p class="mt-1 text-sm text-slate-500">{{ $letter->recipient_name }} · {{ $letter->letterType?->name }}</p></div><div class="flex flex-wrap gap-2"><a href="{{ route('outgoing-letters.show', $letter->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Preview</a>@if($letter->status->value === 'draft')<button wire:click="validateLetter('{{ $letter->id }}')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Validate</button>@endif @if($letter->status->value === 'validated')<button wire:click="issue('{{ $letter->id }}')" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Issue</button>@endif @if($letter->status->value === 'issued')<a href="{{ route('verification.show', $letter->verification_token) }}" target="_blank" class="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Verify</a>@endif</div></div>
            @empty<div class="p-12 text-center text-sm text-slate-500">Belum ada surat keluar.</div>@endforelse
        </div><div class="border-t border-slate-100 p-4">{{ $letters->links() }}</div>
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold text-slate-900">Create Outgoing Letter</h2><p class="mt-1 text-sm text-slate-500">Pilih master surat. Field dibuat otomatis dari placeholder yang digunakan di template DOCX.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div><label class="text-sm font-medium text-slate-700">Letter Type</label><select wire:model.live="letter_type_id" class="form-select mt-1"><option value="">Pilih jenis surat</option>@foreach($letterTypes as $type)<option value="{{ $type->id }}">{{ $type->code }} — {{ $type->name }}</option>@endforeach</select>@error('letter_type_id')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                    @if($variables)
                        <div class="rounded-xl border border-slate-200 bg-white p-4"><div><h3 class="text-sm font-semibold text-slate-900">Data Surat</h3><p class="mt-1 text-xs text-slate-500">Hanya placeholder yang benar-benar dipakai template yang ditampilkan.</p></div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                @foreach($variables as $variable)
                                    @php
                                        $label = $variableLabels[$variable] ?? ucwords(str_replace('_',' ',$variable));
                                        $systemVariable = in_array($variable, ['tenant_name','tenant_city','tenant_district','tenant_village','tenant_province','tenant_address','tenant_phone','tenant_email','tenant_head_name','tenant_head_title'], true);
                                        $wide = in_array($variable, ['recipient_address','subject','tenant_address'], true);
                                        $dateVariable = (bool) preg_match('/(^|_)date$/i', $variable);
                                        $birthDateVariable = (bool) preg_match('/(^|_)birth_date$/i', $variable);
                                    @endphp
                                    <div class="{{ $wide ? 'sm:col-span-2' : '' }}">
                                        <div class="flex items-center justify-between gap-3"><label class="text-sm font-medium text-slate-700">{{ $label }}</label>@if($systemVariable)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Otomatis</span>@endif</div>
                                        @if($dateVariable)
                                            <input type="date" wire:model="variableValues.{{ $variable }}" class="form-control mt-1 {{ $systemVariable ? 'bg-slate-50' : '' }}" @if($systemVariable) readonly @endif @if($birthDateVariable) max="{{ now()->subDay()->toDateString() }}" @endif />
                                            <p class="mt-1 text-xs text-slate-400">@if($birthDateVariable)Tanggal lahir harus berupa tanggal dan tidak boleh hari ini atau masa depan.@elseTanggal tidak boleh tanggal hari ini.@endif</p>
                                        @elseif($variable === 'recipient_address' || $variable === 'tenant_address')
                                            <textarea wire:model="variableValues.{{ $variable }}" rows="2" class="form-textarea mt-1 {{ $systemVariable ? 'bg-slate-50' : '' }}" @if($systemVariable) readonly @endif></textarea>
                                        @else
                                            <input wire:model="variableValues.{{ $variable }}" class="form-control mt-1 {{ $systemVariable ? 'bg-slate-50' : '' }}" @if($systemVariable) readonly @endif>
                                        @endif
                                        @if($systemVariable)<p class="mt-1 text-xs text-slate-400">Diambil otomatis dari data organisasi / sistem.</p>@endif
                                        <x-input-error :messages="$errors->get('variableValues.'.$variable)" class="mt-1" />
                                    </div>
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
