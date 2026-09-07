<div
    class="space-y-6"
    x-data
    x-init="
        const typeId = new URLSearchParams(window.location.search).get('letter_type_id');
        if (typeId) {
            window.history.replaceState({}, '', window.location.pathname + window.location.hash);
            $wire.create().then(() => $wire.set('letter_type_id', typeId));
        }
    "
>
    @include('livewire.pages.outgoing-letters.partials.toolbar')

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="divide-y divide-slate-100">
            @forelse ($letters as $letter)
                @include('livewire.pages.outgoing-letters.partials.letter-item', ['letter' => $letter])
            @empty
                <div class="p-12 text-center text-sm text-slate-500">{{ $isSuperAdmin && $filter === 'deleted' ? 'Tidak ada surat yang dihapus.' : 'Belum ada surat keluar.' }}</div>
            @endforelse
        </div>
        <x-ui.table-footer :paginator="$letters" label="surat" />
    </div>

    @if($showForm)
        @include('livewire.pages.outgoing-letters.partials.form')
    @endif

    @if($showRejectForm)
        @include('livewire.pages.outgoing-letters.partials.reject-form')
    @endif
</div>
