<?php

declare(strict_types=1);

use App\Models\LetterClassification;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filter = 'active';
    public int $perPage = 5;
    public bool $showForm = false;
    public ?string $editingId = null;
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public string $number_format = '{{number}}/{{classification_code}}/{{tenant_code}}/{{year}}';
    public int $number_padding = 3;
    public int $sort_order = 1;
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->perPage = max(5, min($this->perPage, 100)); $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->sort_order = ((int) LetterClassification::query()->max('sort_order')) + 1;
        $this->showForm = true;
    }

    public function openEdit(string $id): void
    {
        $classification = LetterClassification::query()->findOrFail($id);
        $this->editingId = $classification->id;
        $this->code = $classification->code;
        $this->name = $classification->name;
        $this->description = (string) $classification->description;
        $this->number_format = $classification->number_format;
        $this->number_padding = $classification->number_padding;
        $this->sort_order = $classification->sort_order;
        $this->is_active = $classification->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(AuditLogService $auditLogService): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        Validator::make(
            [
                'code' => $this->code,
                'name' => $this->name,
                'description' => $this->description,
                'number_format' => $this->number_format,
                'number_padding' => $this->number_padding,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ],
            [
                'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
                'name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string'],
                'number_format' => ['required', 'string', 'max:255', 'regex:/\{\{\s*number\s*\}\}/i'],
                'number_padding' => ['required', 'integer', 'min:1', 'max:12'],
                'sort_order' => ['required', 'integer', 'min:1', 'max:65535'],
                'is_active' => ['boolean'],
            ],
            [
                'number_format.regex' => 'Format wajib memiliki variabel {{number}}.',
            ],
        )->validate();

        $classification = $this->editingId
            ? LetterClassification::query()->findOrFail($this->editingId)
            : new LetterClassification();

        $old = $classification->exists ? [
            'code' => $classification->code,
            'name' => $classification->name,
            'number_format' => $classification->number_format,
            'number_padding' => $classification->number_padding,
            'sort_order' => $classification->sort_order,
            'is_active' => $classification->is_active,
        ] : null;

        $classification->fill([
            'code' => trim($this->code),
            'name' => trim($this->name),
            'description' => filled($this->description) ? trim($this->description) : null,
            'number_format' => trim($this->number_format),
            'number_padding' => $this->number_padding,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);
        $classification->save();

        $auditLogService->record(
            action: $classification->wasRecentlyCreated ? 'letter-classification.created' : 'letter-classification.updated',
            user: auth()->user(),
            auditable: $classification,
            oldValues: $old,
            newValues: [
                'code' => $classification->code,
                'name' => $classification->name,
                'number_format' => $classification->number_format,
                'number_padding' => $classification->number_padding,
                'sort_order' => $classification->sort_order,
                'is_active' => $classification->is_active,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Klasifikasi surat berhasil disimpan.');
    }

    public function toggleActive(string $id, AuditLogService $auditLogService): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $classification = LetterClassification::query()->findOrFail($id);
        $old = $classification->is_active;
        $classification->update(['is_active' => ! $old]);
        $auditLogService->record(
            action: 'letter-classification.status_updated',
            user: auth()->user(),
            auditable: $classification,
            oldValues: ['is_active' => $old],
            newValues: ['is_active' => $classification->is_active],
        );

        $this->dispatch('toast', type: 'success', message: 'Status klasifikasi diperbarui.');
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->description = '';
        $this->number_format = '{{number}}/{{classification_code}}/{{tenant_code}}/{{year}}';
        $this->number_padding = 3;
        $this->sort_order = 1;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function classifications()
    {
        return LetterClassification::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'ilike', '%'.$this->search.'%')->orWhere('code', 'ilike', '%'.$this->search.'%')))
            ->when($this->filter === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filter === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate($this->perPage);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Klasifikasi Surat</h1>
            <p class="mt-1 text-sm text-slate-500">Master klasifikasi sekaligus aturan format nomor surat.</p>
        </div>
        <button type="button" wire:click="openCreate" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">＋ Tambah Klasifikasi</button>
    </div>

    @php($classifications = $this->classifications())
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex rounded-xl bg-slate-100 p-1">
                <button type="button" wire:click="$set('filter','active')" class="rounded-lg px-4 py-2 text-sm font-medium {{ $filter==='active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Active</button>
                <button type="button" wire:click="$set('filter','inactive')" class="rounded-lg px-4 py-2 text-sm font-medium {{ $filter==='inactive' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Inactive</button>
            </div>
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode atau klasifikasi..." class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 sm:w-80">
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($classifications as $classification)
                <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-bold text-slate-700">{{ $classification->code }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $classification->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $classification->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <h2 class="mt-1 text-sm font-semibold text-slate-900">{{ $classification->name }}</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $classification->description ?: 'Tidak ada deskripsi.' }}</p>
                        <p class="mt-2 font-mono text-xs text-indigo-700">{{ $classification->number_format }}</p>
                        <p class="mt-1 text-xs text-slate-400">Nomor {{ $classification->number_padding }} digit · Urutan {{ $classification->sort_order }}</p>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <button type="button" wire:click="openEdit('{{ $classification->id }}')" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Edit</button>
                        <button type="button" wire:click="toggleActive('{{ $classification->id }}')" wire:confirm="Ubah status klasifikasi ini?" class="rounded-lg border px-3 py-2 text-sm font-medium {{ $classification->is_active ? 'border-rose-200 text-rose-600 hover:bg-rose-50' : 'border-emerald-200 text-emerald-600 hover:bg-emerald-50' }}">{{ $classification->is_active ? 'Disable' : 'Enable' }}</button>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-sm text-slate-400">Belum ada klasifikasi surat.</div>
            @endforelse
        </div>
        @if($classifications->count())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-4"><div class="flex items-center gap-2"><label for="classification-per-page" class="text-xs text-slate-500">Show</label><select id="classification-per-page" wire:model.live="perPage" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700"><option value="5">5</option><option value="10">10</option><option value="25">25</option><option value="50">50</option></select></div><p class="text-xs text-slate-500">Showing {{ $classifications->firstItem() }} – {{ $classifications->lastItem() }} of {{ $classifications->total() }} classifications</p></div><x-ui.pagination :paginator="$classifications" /></div></div>
        @endif
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Klasifikasi Surat' : 'Tambah Klasifikasi Surat' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Format nomor mendukung <code>{{'{{number}}'}}</code>, <code>{{'{{classification_code}}'}}</code>, <code>{{'{{tenant_code}}'}}</code>, <code>{{'{{year}}'}}</code>, <code>{{'{{month}}'}}</code>, dan <code>{{'{{month_roman}}'}}</code>.</p>
                </div>
                <form wire:submit="save" class="space-y-5 p-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Code</label><input wire:model="code" class="form-control mt-1" placeholder="400.10"><p class="mt-1 text-xs text-slate-400">Simpan sesuai klasifikasi resmi yang dipakai instansi.</p>@error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Nama</label><input wire:model="name" class="form-control mt-1" placeholder="Kesejahteraan Rakyat">@error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    </div>
                    <div><label class="text-sm font-medium text-slate-700">Deskripsi</label><textarea wire:model="description" rows="2" class="form-textarea mt-1"></textarea></div>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                        <label class="text-sm font-semibold text-indigo-900">Format Penomoran</label>
                        <input wire:model="number_format" class="form-control mt-2 font-mono" placeholder="{{'{{number}}'}}/{{'{{classification_code}}'}}/{{'{{tenant_code}}'}}/{{'{{year}}'}}">
                        @error('number_format') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-indigo-700">Contoh hasil: <code>001/400.10/DINKES/2026</code>. Nomor urut otomatis terpisah untuk setiap tenant + klasifikasi + tahun.</p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Jumlah digit nomor</label><input wire:model="number_padding" type="number" min="1" max="12" class="form-control mt-1">@error('number_padding') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                        <div><label class="text-sm font-medium text-slate-700">Urutan</label><input wire:model="sort_order" type="number" min="1" class="form-control mt-1">@error('sort_order') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror</div>
                    </div>
                    <label class="flex items-center gap-3 text-sm font-medium text-slate-700"><input wire:model="is_active" type="checkbox" class="rounded border-slate-300"> Klasifikasi aktif dan dapat dipilih oleh jenis surat</label>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-5"><button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</button><button type="submit" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Simpan</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
