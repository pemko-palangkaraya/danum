<div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <section class="rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm sm:p-6">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Bagan Struktur</h2>
                <p class="text-xs text-slate-500">Atur hubungan jabatan dan pemangkunya langsung dari bagan.</p>
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
            <h2 class="text-base font-semibold text-slate-900">Status Pemangku</h2>
            <div class="mt-3 space-y-2 text-xs text-slate-600">
                @foreach($assignmentStatuses as $status)
                    <div class="flex items-start gap-2"><span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span><div><span class="font-semibold text-slate-800">{{ $status->label() }}</span></div></div>
                @endforeach
            </div>
        </div>
    </aside>
</div>
