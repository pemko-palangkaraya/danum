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

    public string $search = '';
    public string $filter = 'active';
    public int $perPage = 5;
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $code = '';
    public int $sort_order = 1;
    public bool $is_active = true;

    public function mount(): void { abort_unless(auth()->user()?->isSuperAdmin(), 403); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }
    public function updatedPerPage(): void { $this->perPage = max(5, min($this->perPage, 100)); $this->resetPage(); }

    public function openCreate(): void { $this->resetForm(); $this->sort_order = ((int) TenantCategory::query()->max('sort_order')) + 1; $this->showForm = true; }
    public function openEdit(int $id): void { $category = TenantCategory::query()->findOrFail($id); $this->editingId=$category->id; $this->name=$category->name; $this->code=$category->code; $this->sort_order=$category->sort_order; $this->is_active=$category->is_active; $this->resetValidation(); $this->showForm=true; }

    public function save(AuditLogService $auditLogService): void {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        Validator::make(['name'=>$this->name,'code'=>$this->code,'sort_order'=>$this->sort_order,'is_active'=>$this->is_active], ['name'=>['required','string','max:150'],'code'=>['required','string','max:50','alpha_dash'],'sort_order'=>['required','integer','min:1','max:65535'],'is_active'=>['boolean']])->validate();
        $category = $this->editingId ? TenantCategory::query()->findOrFail($this->editingId) : new TenantCategory();
        $old = $category->exists ? ['code'=>$category->code,'name'=>$category->name,'sort_order'=>$category->sort_order,'is_active'=>$category->is_active] : null;
        $category->fill(['name'=>trim($this->name),'code'=>Str::slug(trim($this->code)),'sort_order'=>$this->sort_order,'is_active'=>$this->is_active]);
        $category->save();
        $auditLogService->record(action:$category->wasRecentlyCreated?'tenant-category.created':'tenant-category.updated',user:auth()->user(),auditable:$category,oldValues:$old,newValues:['code'=>$category->code,'name'=>$category->name,'sort_order'=>$category->sort_order,'is_active'=>$category->is_active]);
        $this->resetForm(); $this->dispatch('toast',type:'success',message:'Kategori tenant berhasil disimpan.');
    }

    public function toggleActive(int $id, AuditLogService $auditLogService): void {
        abort_unless(auth()->user()?->isSuperAdmin(), 403); $category=TenantCategory::query()->findOrFail($id); $old=$category->is_active; $category->update(['is_active'=>!$old]);
        $auditLogService->record(action:'tenant-category.status_updated',user:auth()->user(),auditable:$category,oldValues:['is_active'=>$old],newValues:['is_active'=>$category->is_active]);
        $this->dispatch('toast',type:'success',message:'Status kategori diperbarui.');
    }

    public function resetForm(): void { $this->showForm=false; $this->editingId=null; $this->name=''; $this->code=''; $this->sort_order=1; $this->is_active=true; $this->resetValidation(); }

    public function categories() {
        return TenantCategory::query()->when($this->search !== '', fn($q)=>$q->where(fn($q)=>$q->where('name','ilike','%'.$this->search.'%')->orWhere('code','ilike','%'.$this->search.'%')))->when($this->filter==='active',fn($q)=>$q->where('is_active',true))->when($this->filter==='inactive',fn($q)=>$q->where('is_active',false))->orderBy('sort_order')->orderBy('name')->paginate($this->perPage);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Tenant Categories</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola kategori organisasi yang menggunakan DANUM.</p>
        </div>
        <button type="button" wire:click="openCreate" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">＋ Add Category</button>
    </div>

    @php($categories=$this->categories())
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex rounded-xl bg-slate-100 p-1">
                <button type="button" wire:click="$set('filter','active')" class="rounded-lg px-4 py-2 text-sm font-medium {{ $filter==='active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Active</button>
                <button type="button" wire:click="$set('filter','inactive')" class="rounded-lg px-4 py-2 text-sm font-medium {{ $filter==='inactive' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">Inactive</button>
            </div>
            <div class="relative w-full sm:w-80">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">⌕</span>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search category..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">
            </div>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Code</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Category</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Sort Order</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $category)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4 text-sm font-semibold text-slate-700">{{ $category->code }}</td>
                            <td class="px-5 py-4"><div class="text-sm font-semibold text-slate-900">{{ $category->name }}</div><div class="mt-0.5 text-xs text-slate-400">Tenant category</div></td>
                            <td class="px-5 py-4 text-sm text-slate-600">{{ $category->sort_order }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $category->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-5 py-4 text-right">
                                <div x-data="{ open: false, top: 0, left: 0, place() { this.$nextTick(() => { const trigger = this.$refs.trigger; const menu = this.$refs.menu; if (!trigger || !menu) return; const rect = trigger.getBoundingClientRect(); const gap = 8; const menuHeight = menu.offsetHeight; const menuWidth = menu.offsetWidth; let nextTop = rect.top - menuHeight - gap; if (nextTop < gap) nextTop = rect.bottom + gap; if (nextTop + menuHeight > window.innerHeight - gap) nextTop = Math.max(gap, window.innerHeight - menuHeight - gap); let nextLeft = rect.right - menuWidth; nextLeft = Math.max(gap, Math.min(nextLeft, window.innerWidth - menuWidth - gap)); this.top = nextTop; this.left = nextLeft; }); } }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" x-on:resize.window="if (open) place()" class="relative inline-block text-left">
                                    <button x-ref="trigger" type="button" x-on:click="open = !open; if (open) place()" x-bind:aria-expanded="open" aria-haspopup="menu" aria-label="Aksi kategori" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
                                    </button>
                                    <div x-ref="menu" x-show="open" x-cloak x-transition x-bind:style="`top: ${top}px; left: ${left}px;`" class="fixed z-[100] w-44 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-xl ring-1 ring-black/5" role="menu">
                                        <button type="button" x-on:click="open = false" wire:click="openEdit({{ $category->id }})" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" role="menuitem">Edit</button>
                                        <button type="button" x-on:click="open = false" wire:click="toggleActive({{ $category->id }})" wire:confirm="Ubah status kategori ini?" wire:loading.attr="disabled" class="block w-full px-4 py-2.5 text-left text-sm {{ $category->is_active ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }} disabled:opacity-50" role="menuitem">{{ $category->is_active ? 'Disable' : 'Enable' }}</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 lg:hidden">
            @forelse($categories as $category)
                <div class="flex items-center justify-between gap-3 p-4">
                    <div class="min-w-0 flex-1"><div class="text-sm font-semibold text-slate-900">{{ $category->name }}</div><div class="mt-1 text-xs text-slate-500">{{ $category->code }} · {{ $category->sort_order }}</div></div>
                    <div class="shrink-0">
                        <div x-data="{ open: false, top: 0, left: 0, place() { this.$nextTick(() => { const trigger = this.$refs.trigger; const menu = this.$refs.menu; if (!trigger || !menu) return; const rect = trigger.getBoundingClientRect(); const gap = 8; const menuHeight = menu.offsetHeight; const menuWidth = menu.offsetWidth; let nextTop = rect.top - menuHeight - gap; if (nextTop < gap) nextTop = rect.bottom + gap; if (nextTop + menuHeight > window.innerHeight - gap) nextTop = Math.max(gap, window.innerHeight - menuHeight - gap); let nextLeft = rect.right - menuWidth; nextLeft = Math.max(gap, Math.min(nextLeft, window.innerWidth - menuWidth - gap)); this.top = nextTop; this.left = nextLeft; }); } }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" class="relative inline-block text-left">
                            <button x-ref="trigger" type="button" x-on:click="open = !open; if (open) place()" x-bind:aria-expanded="open" aria-haspopup="menu" aria-label="Aksi kategori" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5" aria-hidden="true"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
                            </button>
                            <div x-ref="menu" x-show="open" x-cloak x-transition x-bind:style="`top: ${top}px; left: ${left}px;`" class="fixed z-[100] w-44 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 text-left shadow-xl" role="menu">
                                <button type="button" x-on:click="open=false" wire:click="openEdit({{ $category->id }})" class="block w-full px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50" role="menuitem">Edit</button>
                                <button type="button" x-on:click="open=false" wire:click="toggleActive({{ $category->id }})" wire:confirm="Ubah status kategori ini?" class="block w-full px-4 py-2.5 text-left text-sm {{ $category->is_active ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }}" role="menuitem">{{ $category->is_active ? 'Disable' : 'Enable' }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-400">No categories found.</div>
            @endforelse
        </div>

        @if($categories->count())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 sm:px-6"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-4"><div class="flex items-center gap-2"><label for="category-per-page" class="text-xs text-slate-500">Show</label><select id="category-per-page" wire:model.live="perPage" class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-700"><option value="5">5</option><option value="10">10</option><option value="25">25</option><option value="50">50</option></select></div><p class="text-xs text-slate-500">Showing {{ $categories->firstItem() }} – {{ $categories->lastItem() }} of {{ $categories->total() }} categories</p></div><x-ui.pagination :paginator="$categories" /></div></div>
        @endif
    </div>

    @if($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"><div class="absolute inset-0" wire:click="resetForm"></div><section class="relative z-10 w-full max-w-lg rounded-2xl bg-white shadow-2xl"><div class="flex items-start justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Kategori' : 'Tambah Kategori' }}</h2><p class="mt-1 text-sm text-slate-500">Master kategori tenant.</p></div><button type="button" wire:click="resetForm" class="text-2xl text-slate-400">&times;</button></div><form wire:submit="save" class="space-y-4 p-5"><div><label class="text-sm font-medium text-slate-700">Nama</label><input wire:model="name" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">@error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="text-sm font-medium text-slate-700">Kode</label><input wire:model="code" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">@error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div class="grid gap-4 sm:grid-cols-2"><div><label class="text-sm font-medium text-slate-700">Urutan</label><input wire:model="sort_order" type="number" min="1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm"></div><label class="flex items-center gap-2 pt-7 text-sm text-slate-700"><input type="checkbox" wire:model="is_active" class="rounded border-slate-300"> Aktif</label></div><div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700">Batal</button><button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Simpan</button></div></form></section></div>
    @endif
</div>
