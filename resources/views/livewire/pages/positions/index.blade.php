<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Jabatan</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola jabatan dan pejabat yang berwenang menandatangani atau memverifikasi surat.</p>
        </div>
        <button type="button" wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">+ Tambah Jabatan</button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode atau nama jabatan..." class="form-control sm:max-w-sm">
        <select wire:model.live="filter" class="form-select sm:w-44">
            <option value="active">Aktif</option>
            <option value="inactive">Tidak Aktif</option>
            <option value="all">Semua</option>
            <option value="deleted">Dihapus</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Jabatan</th>
                        <th class="px-5 py-3">Pemegang Aktif</th>
                        <th class="px-5 py-3">Penandatangan</th>
                        <th class="px-5 py-3">Verifikator</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($positions as $position)
                        @php($holder = $position->holders->first(fn ($item) => $item->ended_at === null && $item->started_at?->lte(now())))
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-semibold text-slate-900">{{ $position->name }}</div>
                                <div class="text-xs text-slate-500">{{ $position->code }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if($holder?->user)
                                    <div class="font-medium">{{ $holder->user->name }}</div>
                                    <div class="text-xs text-slate-500">Mulai {{ $holder->started_at?->format('d M Y') }}</div>
                                @else
                                    <span class="text-slate-400">Belum aktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($position->can_sign)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Boleh TTE</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Tidak</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($position->can_validate)
                                    <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Boleh Verifikasi</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">Tidak</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $position->status->value === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $position->status->value === 'active' ? 'Aktif' : 'Tidak Aktif' }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" wire:click="assignHolder('{{ $position->id }}')" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Pejabat</button>
                                    <button type="button" wire:click="edit('{{ $position->id }}')" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Edit</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada jabatan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $positions->links() }}</div>
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold">{{ $editingId ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Tentukan apakah jabatan ini dapat menjadi penanda tangan TTE dan/atau verifikator surat.</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Kode Jabatan</label>
                        <input wire:model="code" type="text" placeholder="Contoh: LURAH" class="form-control mt-1">
                        @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Nama Jabatan</label>
                        <input wire:model="name" type="text" placeholder="Contoh: Lurah" class="form-control mt-1">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Deskripsi <span class="font-normal text-slate-400">(opsional)</span></label>
                        <textarea wire:model="description" rows="3" placeholder="Deskripsi jabatan..." class="form-textarea mt-1"></textarea>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Status</label>
                        <select wire:model="status" class="form-select mt-1">
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                        <input wire:model="can_sign" type="checkbox" class="mt-0.5 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-semibold">Jabatan dapat menandatangani</span>
                            <span class="block text-xs text-slate-500">Hanya pemegang aktif jabatan ini yang nantinya dapat dipilih sebagai penanda tangan.</span>
                        </span>
                    </label>
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4">
                        <input wire:model="can_validate" type="checkbox" class="mt-0.5 rounded border-slate-300">
                        <span>
                            <span class="block text-sm font-semibold">Jabatan dapat melakukan verifikasi</span>
                            <span class="block text-xs text-slate-500">Hanya pemegang aktif jabatan ini yang dapat dipilih sebagai verifikator dan melakukan verifikasi surat.</span>
                        </span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button wire:click="save" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan</button>
                </div>
            </div>
        </div>
    @endif

    @if($showHolderForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showHolderForm', false)">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <h2 class="text-lg font-semibold">Tetapkan Pemegang Jabatan</h2>
                <p class="mt-1 text-sm text-slate-500">Pemegang lama otomatis diakhiri ketika pejabat baru mulai.</p>
                <div class="mt-5 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Pejabat</label>
                        <select wire:model="holderUserId" class="form-select mt-1">
                            <option value="">Pilih user...</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>
                            @endforeach
                        </select>
                        @error('holderUserId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Tanggal Mulai</label>
                        <input wire:model="holderStartedAt" type="date" class="form-control mt-1">
                        @error('holderStartedAt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showHolderForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button>
                    <button wire:click="saveHolder" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Tetapkan</button>
                </div>
            </div>
        </div>
    @endif
</div>