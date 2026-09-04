@php
    $submitted = $letter->submitted_at !== null;
    $rejected = filled($letter->rejection_reason);
    $effectiveState = $letter->status->value === 'withdrawn'
        ? 'withdrawn'
        : ($letter->status->value === 'issued' && $letter->isExpired() ? 'expired' : $letter->status->value);
@endphp

<div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between {{ $letter->trashed() ? 'bg-rose-50/50' : '' }}">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-mono text-xs font-semibold text-slate-400">{{ $letter->number }}</span>
            <span @class([
                'rounded-full px-2 py-0.5 text-xs font-medium',
                'bg-emerald-100 text-emerald-700' => $effectiveState === 'issued',
                'bg-amber-100 text-amber-800' => in_array($effectiveState, ['validated', 'expired'], true),
                'bg-red-100 text-red-700' => in_array($effectiveState, ['withdrawn', 'cancelled'], true),
                'bg-blue-100 text-blue-800' => $letter->status->value === 'draft' && $submitted,
                'bg-slate-100 text-slate-600' => $effectiveState === 'draft' && ! $submitted,
            ])>{{ $submitted && $letter->status->value === 'draft' ? 'Menunggu Verifikasi' : ($rejected ? 'Ditolak' : match($effectiveState) {
                'withdrawn' => 'Ditarik', 'expired' => 'Kedaluwarsa', 'issued' => 'Issued', 'validated' => 'Validated', 'cancelled' => 'Cancelled', default => 'Draft'
            }) }}</span>
            @if($letter->trashed())<span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700">Deleted</span>@endif
        </div>
        <h2 class="mt-1 font-semibold text-slate-900">{{ $letter->subject }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $letter->recipient_name }} · {{ $letter->letterType?->name }}@if($isSuperAdmin && $letter->tenant) · {{ $letter->tenant->name }}@endif</p>
        @if($letter->signer_name)<p class="mt-1 text-xs text-slate-500">Penanda tangan: <span class="font-medium text-slate-700">{{ $letter->signer_name }}</span> · {{ $letter->signer_title }}</p>@endif
        @if($letter->validator_name)<p class="mt-1 text-xs text-slate-500">Verifikator: <span class="font-medium text-slate-700">{{ $letter->validator_name }}</span> · {{ $letter->validator_title }}</p>@endif
        @if($rejected)
            <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                <div><span class="font-semibold">Ditolak oleh:</span> {{ $letter->rejectedBy?->name ?? 'Pengguna tidak diketahui' }}@if($letter->rejected_at) · {{ $letter->rejected_at->format('d M Y H:i') }}@endif</div>
                <div class="mt-0.5"><span class="font-semibold">Alasan:</span> {{ $letter->rejection_reason }}</div>
            </div>
        @endif
    </div>

    <div class="flex flex-wrap gap-2">
        @if(! $letter->trashed())
            @can('view', $letter)<a href="{{ route('outgoing-letters.show', $letter->id) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Preview</a>@endcan
            @unless($isSuperAdmin)
                @if($letter->status->value === 'draft' && ! $submitted && (int) $letter->created_by === (int) auth()->id())
                    @can('update', $letter)<button wire:click="edit('{{ $letter->id }}')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</button>@endcan
                    @can('submit', $letter)<button wire:click="submitLetter('{{ $letter->id }}')" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Submit</button>@endcan
                @endif
                @if($submitted && $letter->status->value === 'draft' && (int) $letter->validator_user_id === (int) auth()->id())
                    @can('validate', $letter)<button wire:click="validateLetter('{{ $letter->id }}')" class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-50">Verifikasi</button>@endcan
                    @can('reject', $letter)<button wire:click="openReject('{{ $letter->id }}')" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Tolak</button>@endcan
                @endif
                @if($letter->status->value === 'validated' && (int) $letter->signer_user_id === (int) auth()->id())
                    @can('issue', $letter)<button wire:click="issue('{{ $letter->id }}')" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Issue</button>@endcan
                    @can('reject', $letter)<button wire:click="openReject('{{ $letter->id }}')" class="rounded-lg border border-red-200 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Tolak</button>@endcan
                @endif
            @endunless
            @if($letter->status->value === 'issued')<a href="{{ route('verification.show', $letter->verification_token) }}" target="_blank" class="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Verify</a>@endif
        @elseif($isSuperAdmin)
            @can('restore', $letter)<button wire:click="restoreLetter('{{ $letter->id }}')" wire:confirm="Restore surat ini?" class="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Restore</button>@endcan
        @endif
    </div>
</div>