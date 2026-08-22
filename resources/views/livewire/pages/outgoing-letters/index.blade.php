<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><h1 class="text-2xl font-semibold tracking-tight text-slate-900">Outgoing Letters</h1><p class="mt-1 text-sm text-slate-500">Buat, validasi, terbitkan, dan verifikasi surat keluar.</p></div>
        <button wire:click="create" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">+ Create Letter</button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
            <input wire:model.live="search" type="search" placeholder="Cari nomor, penerima, perihal..." class="w-full rounded-xl border-slate-200 text-sm sm:max-w-md">
            <select wire:model.live="filter" class="rounded-xl border-slate-200 text-sm sm:w-44">
                <option value="all">All</option><option value="draft">Draft</option><option value="validated">Validated</option><option value="issued">Issued</option><option value="cancelled">Cancelled</option>
            </select>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($letters as $letter)
                <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-xs font-semibold text-slate-400">{{ $letter->number }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ $letter->status->value }}</span>@if($letter->letterTypeVersion)<span class="text-xs text-slate-400">template v{{ $letter->letterTypeVersion->version }}</span>@endif</div>
                        <h2 class="mt-1 font-semibold text-slate-900">{{ $letter->subject }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $letter->recipient_name }} · {{ $letter->letterType?->name }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($letter->status->value === 'draft')<button wire:click="validateLetter('{{ $letter->id }}')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium hover:bg-slate-50">Validate</button>@endif
                        @if($letter->status->value === 'validated')<button wire:click="issue('{{ $letter->id }}')" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Issue</button>@endif
                        @if($letter->status->value === 'issued')<a href="{{ route('verification.show', $letter->verification_token) }}" target="_blank" class="rounded-lg border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50">Verify</a>@endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">Belum ada surat keluar.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-100 p-4">{{ $letters->links() }}</div>
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5"><h2 class="text-lg font-semibold">Create Outgoing Letter</h2><p class="mt-1 text-sm text-slate-500">Draft akan menyimpan template version yang digunakan.</p></div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div><label class="text-sm font-medium">Letter Type</label><select wire:model="letter_type_id" class="mt-1 w-full rounded-xl border-slate-200"><option value="">Pilih jenis surat</option>@foreach($letterTypes as $type)<option value="{{ $type->id }}">{{ $type->code }} — {{ $type->name }}</option>@endforeach</select></div>
                    <div class="grid gap-5 sm:grid-cols-2"><div><label class="text-sm font-medium">Number</label><input wire:model="number" class="mt-1 w-full rounded-xl border-slate-200"></div><div><label class="text-sm font-medium">Subject</label><input wire:model="subject" class="mt-1 w-full rounded-xl border-slate-200"></div></div>
                    <div><label class="text-sm font-medium">Recipient</label><input wire:model="recipient_name" class="mt-1 w-full rounded-xl border-slate-200"></div>
                    <div><label class="text-sm font-medium">Address</label><textarea wire:model="recipient_address" rows="2" class="mt-1 w-full rounded-xl border-slate-200"></textarea></div>
                    @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>@endif
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="$set('showForm', false)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold">Cancel</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Create Draft</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
