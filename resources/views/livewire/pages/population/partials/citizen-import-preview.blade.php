@if($importErrors)
    <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
        <ul class="list-disc space-y-1 pl-5">
            @foreach($importErrors as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

@if($ready)
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                                @if($row['_error'])
                                    <span class="text-red-600">{{ $row['_error'] }}</span>
                                @elseif(!empty($row['_duplicate']))
                                    <span class="text-amber-600">Akan diperbarui</span>
                                @else
                                    <span class="text-emerald-600">Siap diimpor</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
