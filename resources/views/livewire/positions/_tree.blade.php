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
                <button type="button" wire:click="editStructure('{{ $nodePosition->id }}')" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Atur Posisi</button>
            @endif
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            @forelse($activeHolders as $holder)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                    {{ $holder->user?->name ?? 'Pengguna' }}
                    <span class="font-semibold {{ $holder->assignment_status === 'plt' ? 'text-amber-700' : 'text-emerald-700' }}">{{ strtoupper($holder->assignment_status ?? 'definitif') }}</span>
                </span>
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
