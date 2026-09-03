@php
    $nodePosition = $node['position'];
    $nodeStructure = $node['structure'];
    $children = $nodes->filter(fn ($item) => (string) ($item['structure']?->parent_position_id) === (string) $nodePosition->id)->sortBy(fn ($item) => [$item['structure']?->sort_order ?? 0, $item['position']->name])->values();
    $activeHolders = $nodePosition->holders->filter(fn ($holder) => $holder->started_at?->lte(now()));
@endphp

<div class="relative" style="margin-left: {{ min($depth * 1.5, 6) }}rem">
    <div class="rounded-2xl border {{ $nodeStructure?->is_root ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white' }} p-4 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    @if($nodeStructure?->is_root)<span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-blue-700">Kepala Organisasi</span>@endif
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $nodePosition->position_type?->label() ?? 'Jabatan' }}</span>
                </div>
                <h3 class="mt-1 text-base font-bold text-slate-900">{{ $nodePosition->name }}</h3>
                <p class="text-xs text-slate-500">{{ $nodePosition->code }}</p>
            </div>
            @if($canManage)
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="editStructure('{{ $nodePosition->id }}')" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Atur Posisi</button>
                    <button type="button" wire:click="openHolderForm('{{ $nodePosition->id }}')" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Atur Pemangku</button>
                </div>
            @endif
        </div>

        <div class="mt-3 space-y-2">
            @forelse($activeHolders as $holder)
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                    <div>
                        <div class="text-sm font-semibold text-slate-800">{{ $holder->user?->name ?? 'Pengguna' }}</div>
                        <div class="mt-0.5 text-[11px] text-slate-500">Mulai {{ $holder->started_at?->translatedFormat('d F Y') }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase {{ $holder->assignment_status === 'definitif' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $holder->assignment_status ?? 'definitif' }}</span>
                        @if($holder->appointment_document_path)
                            <a href="{{ route('positions.appointment-document', $holder) }}" target="_blank" class="text-[11px] font-semibold text-blue-700 hover:text-blue-900">Lihat SK</a>
                        @endif
                    </div>
                </div>
                @if($holder->appointment_number)
                    <div class="px-3 text-[11px] text-slate-500">SK: <span class="font-medium text-slate-700">{{ $holder->appointment_number }}</span></div>
                @endif
            @empty
                <span class="text-xs italic text-slate-400">Belum ada pemangku jabatan</span>
            @endforelse
        </div>
    </div>

    @if($children->isNotEmpty())
        <div class="mt-3 space-y-3 border-l-2 border-slate-200 pl-4">
            @foreach($children as $child)
                @include('livewire.positions._tree', ['node' => $child, 'nodes' => $nodes, 'depth' => $depth + 1, 'canManage' => $canManage])
            @endforeach
        </div>
    @endif
</div>
