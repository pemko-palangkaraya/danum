<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-semibold tracking-tight text-slate-900">Jabatan</h1><p class="mt-1 text-sm text-slate-500">Kelola master jabatan, pejabat, dan kredensial TTE.</p></div>
        @if($isSuperAdmin)<button type="button" wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">+ Tambah Jabatan</button>@endif
    </div>

    @if($isSuperAdmin)<div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">Jabatan adalah master organisasi. Buat jabatan sekali di sini; pergantian pejabat dicatat melalui riwayat pemegang jabatan.</div>@endif

    <div class="flex flex-col gap-3 sm:flex-row">
        @if($isSuperAdmin)<select wire:model.live="selectedTenantId" class="form-select sm:w-72"><option value="">Pilih organisasi...</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>@endif
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode atau nama jabatan..." class="form-control sm:max-w-sm">
        <select wire:model.live="filter" class="form-select sm:w-44"><option value="active">Aktif</option><option value="inactive">Tidak Aktif</option><option value="all">Semua</option>@if($isSuperAdmin)<option value="deleted">Dihapus</option>@endif</select>
    </div>

    <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:block">
        <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Jabatan</th><th class="px-5 py-3">Pejabat Aktif</th><th class="px-5 py-3">Penandatangan</th><th class="px-5 py-3">Verifikator</th><th class="px-5 py-3">Sertifikat TTE</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($positions as $position)
                    @php($holder = $position->holders->first(fn ($item) => $item->ended_at === null && $item->started_at?->lte(now())))
                    @php($certificate = $position->signerCertificates->first())
                    <tr>
                        <td class="px-5 py-4"><div class="font-semibold text-slate-900">{{ $position->name }}</div><div class="text-xs text-slate-500">{{ $position->code }}</div></td>
                        <td class="px-5 py-4 text-slate-700">@if($holder?->user)<div class="font-medium">{{ $holder->user->name }}</div><div class="text-xs text-slate-500">Mulai {{ $holder->started_at?->format('d M Y') }}</div>@else<span class="text-slate-400">Belum ditentukan</span>@endif</td>
                        <td class="px-5 py-4">@if($position->can_sign)<span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Boleh TTE</span>@else<span class="text-slate-400">Tidak</span>@endif</td>
                        <td class="px-5 py-4">@if($position->can_validate)<span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Boleh Verifikasi</span>@else<span class="text-slate-400">Tidak</span>@endif</td>
                        <td class="px-5 py-4">@if(!$position->can_sign)<span class="text-slate-400">Tidak diperlukan</span>@elseif($certificate && $certificate->isUsable())<div class="font-medium text-emerald-700">Aktif</div><div class="text-xs text-slate-500">s.d. {{ $certificate->valid_until?->format('d M Y') }}</div>@else<span class="text-amber-600">Belum ada</span>@endif</td>
                        <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $position->status->value === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $position->status->value === 'active' ? 'Aktif' : 'Tidak Aktif' }}</span></td>
                        <td class="px-5 py-4 text-right">
                            <div
                                x-data="{ open: false, top: 0, left: 0, place() { this.$nextTick(() => { const trigger = this.$refs.trigger; const menu = this.$refs.menu; if (!trigger || !menu) return; const rect = trigger.getBoundingClientRect(); const gap = 8; const menuHeight = menu.offsetHeight; const menuWidth = menu.offsetWidth; let nextTop = rect.top - menuHeight - gap; if (nextTop < gap) nextTop = rect.bottom + gap; if (nextTop + menuHeight > window.innerHeight - gap) nextTop = Math.max(gap, window.innerHeight - menuHeight - gap); let nextLeft = rect.right - menuWidth; nextLeft = Math.max(gap, Math.min(nextLeft, window.innerWidth - menuWidth - gap)); this.top = nextTop; this.left = nextLeft; }); } }"
                                x-on:click.outside="open = false"
                                x-on:keydown.escape.window="open = false"
                                x-on:resize.window="if (open) place()"
                                class="relative inline-block text-left"
                            >
                                <button x-ref="trigger" type="button" x-on:click="open = !open; if (open) place()" x-bind:aria-expanded="open" aria-haspopup="menu" aria-label="Aksi jabatan" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
                                </button>

                                <div x-ref="menu" x-show="open" x-cloak x-transition x-bind:style="`top: ${top}px; left: ${left}px;`" class="fixed z-[100] w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-xl ring-1 ring-black/5" role="menu">
                                    <button type="button" x-on:click="open = false" wire:click="showHistoryFor('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" role="menuitem">View</button>
                                    <button type="button" x-on:click="open = false" wire:click="assignHolder('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" role="menuitem">Pejabat</button>
                                    @if($position->can_sign && $holder?->user)<button type="button" x-on:click="open = false" wire:click="manageCertificate('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-emerald-700 transition hover:bg-emerald-50 disabled:opacity-50" role="menuitem">Sertifikat TTE</button>@endif
                                    @if($isSuperAdmin)<button type="button" x-on:click="open = false" wire:click="edit('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" role="menuitem">Edit</button>@endif
                                    @if($position->status->value === 'active')<button type="button" x-on:click="open = false" wire:click="toggleStatus('{{ $position->id }}')" wire:confirm="Ubah status jabatan ini?" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-red-600 transition hover:bg-red-50 disabled:opacity-50" role="menuitem">Deactivate</button>@else<button type="button" x-on:click="open = false" wire:click="toggleStatus('{{ $position->id }}')" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-emerald-600 transition hover:bg-emerald-50 disabled:opacity-50" role="menuitem">Activate</button>@endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty<tr><td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada jabatan.</td></tr>@endforelse
            </tbody></table></div>
        <div class="border-t border-slate-100 px-5 py-4">{{ $positions->links() }}</div>
    </div>

    <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:hidden">
        @forelse ($positions as $position)
            @php($holder = $position->holders->first(fn ($item) => $item->ended_at === null && $item->started_at?->lte(now())))
            <div class="flex items-center justify-between gap-3 p-4">
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-semibold text-slate-900">{{ $position->name }}</div>
                    <div class="mt-0.5 truncate text-xs text-slate-500">{{ $position->code }}</div>
                </div>
                <div class="min-w-0 max-w-[45%] text-right">
                    <div class="truncate text-sm font-medium text-slate-800">{{ $holder?->user?->name ?? 'Belum ditentukan' }}</div>
                    <div class="mt-1 flex justify-end gap-1.5">
                        @if($position->can_sign)<span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">TTE</span>@endif
                        @if($position->can_validate)<span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700">Verifikasi</span>@endif
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $position->status->value === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $position->status->value === 'active' ? 'Aktif' : 'Tidak Aktif' }}</span>
                    </div>
                </div>
                <div class="shrink-0">
                    <div
                        x-data="{ open: false, top: 0, left: 0, place() { this.$nextTick(() => { const trigger = this.$refs.trigger; const menu = this.$refs.menu; if (!trigger || !menu) return; const rect = trigger.getBoundingClientRect(); const gap = 8; const menuHeight = menu.offsetHeight; const menuWidth = menu.offsetWidth; let nextTop = rect.top - menuHeight - gap; if (nextTop < gap) nextTop = rect.bottom + gap; if (nextTop + menuHeight > window.innerHeight - gap) nextTop = Math.max(gap, window.innerHeight - menuHeight - gap); let nextLeft = rect.right - menuWidth; nextLeft = Math.max(gap, Math.min(nextLeft, window.innerWidth - menuWidth - gap)); this.top = nextTop; this.left = nextLeft; }); } }"
                        x-on:click.outside="open = false"
                        x-on:keydown.escape.window="open = false"
                        class="relative inline-block text-left"
                    >
                        <button x-ref="trigger" type="button" x-on:click="open = !open; if (open) place()" x-bind:aria-expanded="open" aria-haspopup="menu" aria-label="Aksi jabatan" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
                        </button>
                        <div x-ref="menu" x-show="open" x-cloak x-transition x-bind:style="`top: ${top}px; left: ${left}px;`" class="fixed z-[100] w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-xl" role="menu">
                            <button type="button" x-on:click="open=false" wire:click="showHistoryFor('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">View</button>
                            <button type="button" x-on:click="open=false" wire:click="assignHolder('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">Pejabat</button>
                            @if($position->can_sign && $holder?->user)<button type="button" x-on:click="open=false" wire:click="manageCertificate('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-emerald-700 hover:bg-emerald-50">Sertifikat TTE</button>@endif
                            @if($isSuperAdmin)<button type="button" x-on:click="open=false" wire:click="edit('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">Edit</button>@endif
                            <button type="button" x-on:click="open=false" wire:click="toggleStatus('{{ $position->id }}')" class="block w-full px-4 py-2.5 text-left text-sm {{ $position->status->value === 'active' ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }}">{{ $position->status->value === 'active' ? 'Deactivate' : 'Activate' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-slate-500">Belum ada jabatan.</div>
        @endforelse
    </div>

    @if($showCertificate)
        @php($certificatePosition = $certificatePositionId ? \App\Models\Position::with('signerCertificates')->find($certificatePositionId) : null)
        @php($certificate = $certificatePosition?->signerCertificates?->first())
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showCertificate', false)">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold">Sertifikat TTE</h2><p class="mt-1 text-sm text-slate-500">{{ $certificatePositionName }} · {{ $certificateHolderName }}</p></div><button type="button" wire:click="$set('showCertificate', false)" class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-100">✕</button></div>
                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    @if($certificate && $certificate->isUsable())
                        <div class="flex items-center justify-between gap-3"><div><div class="text-sm font-semibold text-emerald-700">Sertifikat aktif</div><div class="mt-1 text-xs text-slate-500">Berlaku {{ $certificate->valid_from?->format('d M Y') }} — {{ $certificate->valid_until?->format('d M Y') }}</div><div class="mt-2 break-all font-mono text-[11px] text-slate-500">SHA-256: {{ $certificate->fingerprint_sha256 }}</div></div><button type="button" wire:click="downloadCertificate('{{ $certificatePositionId }}')" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Download Public Cert</button></div>
                        <div class="mt-4 border-t border-slate-200 pt-4"><p class="text-xs text-amber-700">Generate ulang akan menonaktifkan sertifikat aktif sebelumnya.</p><button wire:click="generateCertificate" type="button" wire:confirm="Generate sertifikat baru untuk pejabat ini? Sertifikat aktif sebelumnya akan dinonaktifkan." class="mt-3 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Generate Ulang</button></div>
                    @else
                        <div class="text-sm font-semibold text-amber-700">Belum ada sertifikat aktif</div><p class="mt-1 text-xs text-slate-500">DANUM akan membuat pasangan kunci RSA dan sertifikat publik self-signed. Private key disimpan terenkripsi dan tidak pernah ditampilkan.</p><button wire:click="generateCertificate" type="button" class="mt-4 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Generate Sertifikat</button>
                    @endif
                    @error('certificatePositionId')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="mt-5 flex justify-end"><button type="button" wire:click="$set('showCertificate', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Tutup</button></div>
            </div>
        </div>
    @endif

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)"><div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-semibold">{{ $editingId ? 'Edit Jabatan' : 'Tambah Jabatan' }}</h2><p class="mt-1 text-sm text-slate-500">Master jabatan dibuat oleh Super Admin.</p><div class="mt-5 space-y-4">
            @if($isSuperAdmin)<div><label class="text-sm font-medium text-slate-700">Organisasi</label><select wire:model="selectedTenantId" class="form-select mt-1"><option value="">Pilih organisasi...</option>@foreach($tenants as $tenant)<option value="{{ $tenant->id }}">{{ $tenant->name }}</option>@endforeach</select>@error('selectedTenantId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>@endif
            <div><label class="text-sm font-medium text-slate-700">Kode Jabatan</label><input wire:model="code" type="text" placeholder="Contoh: LURAH" class="form-control mt-1">@error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-slate-700">Nama Jabatan</label><input wire:model="name" type="text" placeholder="Contoh: Lurah" class="form-control mt-1">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-slate-700">Deskripsi <span class="font-normal text-slate-400">(opsional)</span></label><textarea wire:model="description" rows="3" class="form-textarea mt-1"></textarea></div><div><label class="text-sm font-medium text-slate-700">Status</label><select wire:model="status" class="form-select mt-1"><option value="active">Aktif</option><option value="inactive">Tidak Aktif</option></select></div><label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4"><input wire:model="can_sign" type="checkbox" class="mt-0.5 rounded border-slate-300"><span><span class="block text-sm font-semibold">Jabatan dapat menandatangani</span><span class="block text-xs text-slate-500">Pemegang aktif jabatan ini dapat dipilih sebagai penanda tangan.</span></span></label><label class="flex items-start gap-3 rounded-xl border border-slate-200 p-4"><input wire:model="can_validate" type="checkbox" class="mt-0.5 rounded border-slate-300"><span><span class="block text-sm font-semibold">Jabatan dapat melakukan verifikasi</span><span class="block text-xs text-slate-500">Pemegang aktif jabatan ini dapat dipilih sebagai verifikator.</span></span></label>
        </div><div class="mt-6 flex justify-end gap-2"><button wire:click="$set('showForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button wire:click="save" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Simpan</button></div></div></div>
    @endif

    @if($showHolderForm)<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showHolderForm', false)"><div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"><h2 class="text-lg font-semibold">Tetapkan Pemegang Jabatan</h2><p class="mt-1 text-sm text-slate-500">Data pejabat aktif dimuat otomatis. Saat diganti, riwayat pejabat sebelumnya tetap tersimpan.</p><div class="mt-5 space-y-4"><div><label class="text-sm font-medium text-slate-700">Pejabat</label><select wire:model="holderUserId" class="form-select mt-1"><option value="">Pilih user...</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} — {{ $user->email }}</option>@endforeach</select>@error('holderUserId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-slate-700">Tanggal Mulai</label><input wire:model="holderStartedAt" type="date" class="form-control mt-1">@error('holderStartedAt')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div><div class="mt-6 flex justify-end gap-2"><button wire:click="$set('showHolderForm', false)" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button wire:click="saveHolder" type="button" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tetapkan</button></div></div></div>@endif

    @if($showHistory)<div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showHistory', false)"><div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl"><div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold">Riwayat Pejabat</h2><p class="mt-1 text-sm text-slate-500">{{ $historyPositionName }}</p></div><button type="button" wire:click="$set('showHistory', false)" class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-100">✕</button></div><div class="mt-5 overflow-hidden rounded-xl border border-slate-200"><table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500"><tr><th class="px-4 py-3">Pejabat</th><th class="px-4 py-3">Mulai</th><th class="px-4 py-3">Berakhir</th><th class="px-4 py-3">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse($history as $holder)<tr><td class="px-4 py-3 font-medium text-slate-800">{{ $holder->user?->name ?? 'User dihapus' }}</td><td class="px-4 py-3">{{ $holder->started_at?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">{{ $holder->ended_at?->format('d M Y') ?? '-' }}</td><td class="px-4 py-3">@if($holder->ended_at === null)<span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Aktif</span>@else<span class="text-slate-500">Selesai</span>@endif</td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada riwayat pejabat.</td></tr>@endforelse</tbody></table></div><div class="mt-5 flex justify-end"><button type="button" wire:click="$set('showHistory', false)" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Tutup</button></div></div></div>@endif
</div>