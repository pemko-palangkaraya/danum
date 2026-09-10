<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Buku Register Surat Keluar</h1>
            <p class="mt-1 text-sm text-slate-500">Catatan seluruh surat keluar, baik yang diterbitkan melalui DANUM maupun secara manual.</p>
        </div>
        @if(auth()->user()->isTenantUser() && auth()->user()->hasPermission('outgoing-letters.create'))
            <button type="button" wire:click="openManualForm" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-slate-800">+ Surat Manual</button>
        @endif
    </div>

    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Tahun</label>
            <input type="number" min="2000" max="2100" wire:model.live="year" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Sumber</label>
            <select wire:model.live="source" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100">
                <option value="all">Semua</option>
                <option value="danum">DANUM</option>
                <option value="manual">Manual</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-xs font-medium text-slate-500">Pencarian</label>
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nomor, perihal, atau tujuan..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-100">
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3 font-semibold">No. Register</th>
                        <th class="px-4 py-3 font-semibold">Nomor Surat</th>
                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                        <th class="px-4 py-3 font-semibold">Perihal</th>
                        <th class="px-4 py-3 font-semibold">Tujuan</th>
                        <th class="px-4 py-3 font-semibold">Sumber</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($entries as $entry)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700">{{ $entry->register_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900">{{ $entry->letter_number }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $entry->letter_date?->format('d/m/Y') }}</td>
                            <td class="max-w-xs px-4 py-3 text-slate-700">{{ $entry->subject }}</td>
                            <td class="max-w-xs px-4 py-3 text-slate-600">{{ $entry->recipient_name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $entry->source->value === 'manual' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700' }}">{{ strtoupper($entry->source->value) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                @if($entry->source->value === 'manual' && auth()->user()->hasPermission('outgoing-letters.update'))
                                    <button type="button" wire:click="editManual('{{ $entry->id }}')" class="text-xs font-medium text-slate-600 hover:text-slate-900">Koreksi</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">Belum ada data register untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-ui.table-footer :paginator="$entries" label="register" />
    </div>

    @if($showManualForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:keydown.escape="$set('showManualForm', false)">
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="flex items-start justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $editingId ? 'Koreksi Register Manual' : 'Tambah Surat Manual' }}</h2>
                        <p class="mt-1 text-xs text-slate-500">Surat ini sudah dibuat di luar DANUM dan hanya dicatat ke buku register.</p>
                    </div>
                    <button type="button" wire:click="$set('showManualForm', false)" class="text-slate-400 hover:text-slate-700">✕</button>
                </div>
                <form wire:submit="saveManual" class="space-y-4 p-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Nomor Surat *</label>
                            <input wire:model="letter_number" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @error('letter_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">Tanggal Surat *</label>
                            <input type="date" wire:model="letter_date" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                            @error('letter_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div><label class="mb-1 block text-xs font-medium text-slate-600">Jenis Surat</label><input wire:model="letter_type_name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="mb-1 block text-xs font-medium text-slate-600">Kode Klasifikasi</label><input wire:model="classification_code" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Perihal *</label><input wire:model="subject" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">@error('subject') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                        <div><label class="mb-1 block text-xs font-medium text-slate-600">Tujuan *</label><input wire:model="recipient_name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">@error('recipient_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div>
                        <div><label class="mb-1 block text-xs font-medium text-slate-600">Penandatangan</label><input wire:model="signer_name" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium text-slate-600">Alamat Tujuan</label><textarea wire:model="recipient_address" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea></div>
                        <div><label class="mb-1 block text-xs font-medium text-slate-600">Jabatan Penandatangan</label><input wire:model="signer_title" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                    </div>
                    @if($editingId)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3">
                            <label class="mb-1 block text-xs font-semibold text-amber-800">Alasan Koreksi *</label>
                            <textarea wire:model="correction_reason" rows="3" class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm"></textarea>
                            @error('correction_reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="$set('showManualForm', false)" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700">Batal</button>
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white">{{ $editingId ? 'Simpan Koreksi' : 'Catat ke Register' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
