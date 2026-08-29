<?php

declare(strict_types=1);

use App\Models\TenantCategory;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public int $perPage = 10;

    public function updatedPerPage(): void
    {
        $this->perPage = max(5, min($this->perPage, 100));
        $this->resetPage();
    }
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public int $sort_order = 1;
    public bool $is_active = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->sort_order = ((int) TenantCategory::query()->max('sort_order')) + 1;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $category = TenantCategory::query()->findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->code = $category->code;
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(AuditLogService $auditLogService): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        Validator::make(
            [
                'name' => $this->name,
                'code' => $this->code,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ],
            [
                'name' => ['required', 'string', 'max:150'],
                'code' => ['required', 'string', 'max:50', 'alpha_dash'],
                'sort_order' => ['required', 'integer', 'min:1', 'max:65535'],
                'is_active' => ['boolean'],
            ],
        )->validate();

        $category = $this->editingId
            ? TenantCategory::query()->findOrFail($this->editingId)
            : new TenantCategory();

        $old = $category->exists ? [
            'code' => $category->code,
            'name' => $category->name,
            'sort_order' => $category->sort_order,
            'is_active' => $category->is_active,
        ] : null;

        $category->fill([
            'name' => trim($this->name),
            'code' => Str::slug(trim($this->code)),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ]);
        $category->save();

        $auditLogService->record(
            action: $category->wasRecentlyCreated ? 'tenant-category.created' : 'tenant-category.updated',
            user: auth()->user(),
            auditable: $category,
            oldValues: $old,
            newValues: [
                'code' => $category->code,
                'name' => $category->name,
                'sort_order' => $category->sort_order,
                'is_active' => $category->is_active,
            ],
        );

        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Kategori tenant berhasil disimpan.');
    }

    public function toggleActive(int $id, AuditLogService $auditLogService): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $category = TenantCategory::query()->findOrFail($id);
        $old = $category->is_active;
        $category->update(['is_active' => ! $old]);

        $auditLogService->record(
            action: 'tenant-category.status_updated',
            user: auth()->user(),
            auditable: $category,
            oldValues: ['is_active' => $old],
            newValues: ['is_active' => $category->is_active],
        );

        $this->dispatch('toast', type: 'success', message: 'Status kategori diperbarui.');
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->code = '';
        $this->sort_order = 1;
        $this->is_active = true;
        $this->resetValidation();
    }

    public function categories()
    {
        return TenantCategory::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($this->perPage);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-slate-500">Master Data</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Tenant Categories</h1>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">Kelola kategori organisasi yang dapat digunakan seluruh tenant di DANUM.</p>
        </div>
        <button type="button" wire:click="openCreate" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">+ Tambah Kategori</button>
    </div>

    @php($categories = $this->categories())
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Daftar Kategori</h2>
                <p class="mt-1 text-xs text-slate-500">Master kategori organisasi.</p>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <span class="whitespace-nowrap">Per halaman</span>
                <select wire:model.live="perPage" class="form-select w-24">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </label>
        </div>
        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Urutan</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Kode</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Kategori</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($categories as $category)
                        <tr>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $category->sort_order }}</td>
                            <td class="px-5 py-4 font-mono text-xs text-slate-500">{{ $category->code }}</td>
                            <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ $category->name }}</td>
                            <td class="px-5 py-4 text-sm">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex gap-2">
                                    <button type="button" wire:click="openEdit({{ $category->id }})" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">Edit</button>
                                    <button type="button" wire:click="toggleActive({{ $category->id }})" wire:confirm="Ubah status kategori ini?" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            @foreach($this->categories() as $category)
                <div class="flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-slate-900">{{ $category->name }}</div>
                        <div class="mt-0.5 text-xs text-slate-500">{{ $category->code }} · Urutan {{ $category->sort_order }}</div>
                    </div>
                    <div class="shrink-0">
                        <div x-data="{open:false}" class="relative">
                            <button type="button" @click="open=!open" aria-label="Aksi kategori" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100">⋮</button>
                            <div x-show="open" x-cloak @click.outside="open=false" class="absolute right-0 z-20 mt-2 w-40 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg">
                                <button type="button" @click="open=false" wire:click="openEdit({{ $category->id }})" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">Edit</button>
                                <button type="button" @click="open=false" wire:click="toggleActive({{ $category->id }})" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50">{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($categories->hasPages())
            <div class="border-t border-slate-100 p-4">
                {{ $categories->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" wire:keydown.escape="resetForm">
            <div class="absolute inset-0" wire:click="resetForm"></div>
            <section class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Kategori' : 'Tambah Kategori' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Master kategori dapat dikembangkan tanpa mengubah struktur tenant.</p>
                    </div>
                    <button type="button" wire:click="resetForm" aria-label="Tutup" class="flex h-9 w-9 items-center justify-center rounded-lg text-2xl text-slate-400 hover:bg-slate-100 hover:text-slate-700">&times;</button>
                </div>
                <form wire:submit="save" class="space-y-4 p-5">
                    <div><label class="text-sm font-medium text-slate-700">Nama</label><input wire:model="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="text-sm font-medium text-slate-700">Kode</label><input wire:model="code" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">@error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="text-sm font-medium text-slate-700">Urutan</label><input wire:model="sort_order" type="number" min="1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"></div>
                        <label class="flex items-center gap-2 pt-7 text-sm text-slate-700"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Aktif</label>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                        <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white"><span wire:loading.remove>Simpan</span><span wire:loading>Menyimpan...</span></button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
