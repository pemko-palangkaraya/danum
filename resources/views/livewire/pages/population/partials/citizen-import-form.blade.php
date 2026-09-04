<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
    @if($isSuperAdmin)
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</label>
            <select wire:model.live="selectedTenantId" class="mt-2 w-full max-w-xl rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                <option value="">Pilih tenant...</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}{{ $tenant->code ? ' ('.$tenant->code.')' : '' }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="text-sm font-medium text-slate-700">File Excel / CSV</label>
            <input wire:model="file" type="file" accept=".xlsx,.xls,.csv" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white p-2 text-sm">
            <p class="mt-1 text-xs text-slate-500">Maksimal 10 MB.</p>
            @error('file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="text-sm font-medium text-slate-700">Jika NIK sudah ada</label>
            <select wire:model.live="duplicateMode" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm">
                <option value="skip">Lewati data yang sudah ada</option>
                <option value="update">Perbarui data yang sudah ada</option>
            </select>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button wire:click="preview" wire:loading.attr="disabled" wire:target="file,preview,import" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">Validasi & Preview</button>
        <a href="{{ route('population.citizens.template') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download Template Excel</a>
    </div>
</div>
