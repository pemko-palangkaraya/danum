<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Struktur Organisasi</h1>
            <p class="mt-1 text-sm text-slate-500">Susun hubungan atasan dan bawahan, kepala organisasi, serta pemangku jabatan.</p>
        </div>
        @if($selectedTenantId !== '')
            @if(auth()->user()?->isSuperAdmin())
                <a href="{{ route('positions.structure.pdf', ['tenant' => $selectedTenantId]) }}" target="_blank" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Cetak Struktur PDF</a>
            @else
                <a href="{{ route('positions.structure.pdf.tenant') }}" target="_blank" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Cetak Struktur PDF</a>
            @endif
        @endif
    </div>

    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
        <div class="font-semibold">Aturan struktur</div>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Jabatan <strong>Manajerial</strong> hanya boleh memiliki satu pemangku aktif, dengan status Definitif atau PLT.</li>
            <li><strong>JFU</strong> dan <strong>JFT</strong> dapat memiliki lebih dari satu pemangku jabatan.</li>
            <li>Hubungan jabatan diatur per kelurahan/tenant, sehingga master jabatan dapat digunakan bersama tanpa memaksa struktur yang sama.</li>
        </ul>
    </div>

    @if(auth()->user()?->isSuperAdmin())
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <label class="text-sm font-semibold text-slate-700">Pilih organisasi</label>
            <select wire:model.live="selectedTenantId" class="form-select mt-2 w-full sm:max-w-md">
                <option value="">Pilih organisasi...</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($selectedTenantId === '')
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-sm text-slate-500">Pilih organisasi untuk melihat struktur.</div>
    @else
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm sm:p-6">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Bagan Struktur</h2>
                        <p class="text-xs text-slate-500">Atur setiap jabatan melalui tombol <strong>Atur Posisi</strong>.</p>
                    </div>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm">{{ $positions->count() }} jabatan</span>
                </div>
                @forelse($roots as $root)
                    @include('livewire.positions._tree', ['node' => $root, 'nodes' => $nodes, 'depth' => 0, 'canManage' => $canManage])
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500">Belum ada jabatan aktif untuk organisasi ini.</div>
                @endforelse
            </section>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Daftar Jabatan</h2>
                    <div class="mt-4 space-y-2">
                        @foreach($positions as $position)
                            @php($structure = $structures->get($position->id))
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-slate-800">{{ $position->name }}</div>
                                        <div class="mt-0.5 text-[11px] text-slate-500">{{ $position->position_type?->label() }}</div>
                                    </div>
                                    @if($structure?->is_root)<span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">ROOT</span>@endif
                                </div>
                                @if($canManage)
                                    <button type="button" wire:click="editStructure('{{ $position->id }}')" class="mt-2 text-xs font-semibold text-blue-700 hover:text-blue-900">Atur hubungan →</button>
                                    @if(!$structure?->is_root)<button type="button" wire:click="setRoot('{{ $position->id }}')" wire:confirm="Jadikan jabatan ini sebagai kepala organisasi?" class="ml-3 text-xs font-semibold text-slate-500 hover:text-slate-800">Jadikan kepala</button>@endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900">Jenis Jabatan</h2>
                    <div class="mt-3 space-y-3 text-xs text-slate-600">
                        <div><div class="font-semibold text-slate-800">Manajerial</div><div>Lurah, Sekretaris Kelurahan, Kasi; satu pemangku aktif.</div></div>
                        <div><div class="font-semibold text-slate-800">JFU</div><div>Jabatan Fungsional Umum; dapat diisi beberapa orang.</div></div>
                        <div><div class="font-semibold text-slate-800">JFT</div><div>Jabatan Fungsional Tertentu; dapat diisi beberapa orang.</div></div>
                    </div>
                </div>
            </aside>
        </div>
    @endif

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                @php($editingPosition = $positions->firstWhere('id', $editingPositionId))
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="text-lg font-semibold text-slate-900">Atur Struktur Jabatan</h2><p class="mt-1 text-sm text-slate-500">{{ $editingPosition?->name }}</p></div>
                    <button type="button" wire:click="$set('showForm', false)" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Tutup">×</button>
                </div>
                <form wire:submit="saveStructure" class="mt-6 space-y-4">
                    <div>
                        <label class="text-sm font-medium text-slate-700">Jenis Jabatan</label>
                        <select wire:model="positionType" class="form-select mt-1 w-full">
                            @foreach($positionTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('positionType')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Berada di bawah</label>
                        <select wire:model="parentPositionId" class="form-select mt-1 w-full">
                            <option value="">— Tidak ada atasan —</option>
                            @foreach($positions as $position)
                                @if($position->id !== $editingPositionId)
                                    <option value="{{ $position->id }}">{{ $position->name }} ({{ $position->position_type?->label() }})</option>
                                @endif
                            @endforeach
                        </select>
                        @error('parentPositionId')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Urutan dalam kelompok</label>
                        <input type="number" min="0" wire:model="sortOrder" class="form-control mt-1 w-full">
                        @error('sortOrder')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 p-3">
                        <input type="checkbox" wire:model="isRoot" class="mt-0.5 rounded border-slate-300">
                        <span><span class="block text-sm font-semibold text-blue-900">Kepala organisasi</span><span class="block text-xs text-blue-700">Jabatan ini menjadi titik paling atas pada bagan dan tidak memiliki atasan.</span></span>
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60"><span wire:loading.remove>Simpan Struktur</span><span wire:loading>Menyimpan...</span></button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
