@php
    use App\Enums\PositionAssignmentStatus;

    $nodePosition = $node['position'];
    $nodeStructure = $node['structure'];
    $children = $nodes
        ->filter(fn ($item) => (string) ($item['structure']?->parent_position_id) === (string) $nodePosition->id)
        ->sortBy(fn ($item) => [$item['structure']?->sort_order ?? 0, $item['position']->name])
        ->values();
    $isRoot = $root ?? $nodeStructure?->is_root;
@endphp

<div class="{{ $isRoot ? 'root-node' : 'nested' }}">
    <div class="box {{ $isRoot ? 'root-box' : '' }}">
        <div class="type">{{ $isRoot ? 'Kepala Organisasi' : ($nodePosition->position_type?->label() ?? 'Jabatan') }}</div>
        <div class="name">{{ $nodePosition->name }}</div>
        @forelse($nodePosition->holders as $holder)
            <div class="holder">
                <strong>{{ $holder->user?->name ?? '—' }}</strong>
                <span>({{ PositionAssignmentStatus::tryFrom($holder->assignment_status)?->label() ?? strtoupper($holder->assignment_status ?? 'definitif') }})</span>
            </div>
            @if($holder->appointment_number)
                <div class="meta">SK: {{ $holder->appointment_number }}</div>
            @endif
            @if($holder->user?->nip)
                <div class="meta">NIP. {{ $holder->user->nip }}</div>
            @endif
        @empty
            <div class="meta">Belum ada pemangku jabatan</div>
        @endforelse
    </div>

    @if($children->isNotEmpty())
        <div class="down-line"></div>
        <div class="children-row">
            @foreach($children as $child)
                <div class="children-cell">
                    @include('organization-structure-pdf-node', [
                        'node' => $child,
                        'nodes' => $nodes,
                        'depth' => $depth + 1,
                        'root' => false,
                    ])
                </div>
            @endforeach
        </div>
    @endif
</div>
