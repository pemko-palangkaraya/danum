@php
    $nodePosition = $node['position'];
    $nodeStructure = $node['structure'];
    $children = $nodes->filter(fn ($item) => (string) ($item['structure']?->parent_position_id) === (string) $nodePosition->id)->sortBy(fn ($item) => [$item['structure']?->sort_order ?? 0, $item['position']->name])->values();
@endphp

<div class="node {{ $nodeStructure?->is_root ? 'root' : '' }}">
    <div class="box">
        <div class="type">{{ $nodeStructure?->is_root ? 'Kepala Organisasi' : ($nodePosition->position_type?->label() ?? 'Jabatan') }}</div>
        <div class="name">{{ $nodePosition->name }}</div>
        @forelse($nodePosition->holders as $holder)
            <div class="holder"><strong>{{ $holder->user?->name ?? '—' }}</strong> ({{ strtoupper($holder->assignment_status ?? 'definitif') }})</div>
            @if($holder->user?->nip)<div class="meta">NIP. {{ $holder->user->nip }}</div>@endif
        @empty
            <div class="meta">Belum ada pemangku jabatan</div>
        @endforelse
    </div>
    @if($children->isNotEmpty())
        <div class="line"></div>
        <div class="children">
            @foreach($children as $child)
                @include('organization-structure-pdf-node', ['node' => $child, 'nodes' => $nodes, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
