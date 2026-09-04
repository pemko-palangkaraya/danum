<div class="relative space-y-6">
    <div wire:loading wire:target="file,preview,import" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/30 backdrop-blur-sm">
        <div class="mx-4 flex w-full max-w-sm items-center gap-4 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-xl">
            <div class="h-10 w-10 shrink-0 animate-spin rounded-full border-4 border-slate-200 border-t-slate-900"></div>
            <div>
                <p class="text-sm font-semibold text-slate-900">Sedang memproses...</p>
                <p class="mt-1 text-xs text-slate-500">Mohon tunggu, terutama untuk file Excel yang besar.</p>
            </div>
        </div>
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Kependudukan</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Import Data Warga</h1>
            <p class="mt-1 text-sm text-slate-500">Impor Excel atau CSV dengan validasi sebelum disimpan.</p>
        </div>
        <a href="{{ route(auth()->user()->isSuperAdmin() ? 'population.admin.citizens.index' : 'population.citizens.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Kembali</a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
        @if(auth()->user()->isSuperAdmin())
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

    @if($importErrors)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach($importErrors as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if($ready)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Preview Import</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $validCount }} baris siap diimpor · {{ $invalidCount }} baris perlu diperiksa.</p>
                </div>
                @if($validCount)
                    <button wire:click="import" wire:loading.attr="disabled" wire:target="import" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="import">Import {{ $validCount }} Data</span>
                        <span wire:loading wire:target="import">Memproses import...</span>
                    </button>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-white"><tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Baris</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIK</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                    </tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach(array_slice($rows, 0, 50) as $row)
                            <tr>
                                <td class="px-5 py-3 text-sm text-slate-500">{{ $row['line'] }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $row['nik'] }}</td>
                                <td class="px-5 py-3 text-sm text-slate-800">{{ $row['nama_lengkap'] }}</td>
                                <td class="px-5 py-3 text-sm">
                                    @if($row['_error'])<span class="text-red-600">{{ $row['_error'] }}</span>@else<span class="text-emerald-600">Siap diimpor</span>@endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
