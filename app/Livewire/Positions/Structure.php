<?php

declare(strict_types=1);

namespace App\Livewire\Positions;

use App\Enums\PositionType;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\TenantPositionStructure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Structure extends Component
{
    public string $selectedTenantId = '';
    public ?string $editingPositionId = null;
    public string $parentPositionId = '';
    public string $sortOrder = '0';
    public bool $isRoot = false;
    public bool $showForm = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Position::class);
        $user = auth()->user();
        if ($user->tenant_id) {
            $this->selectedTenantId = (string) $user->tenant_id;
            $this->ensureStructureRows();
        }
    }

    public function updatedSelectedTenantId(): void
    {
        $this->resetForm();
        if ($this->selectedTenantId !== '') $this->ensureStructureRows();
    }

    public function editStructure(string $positionId): void
    {
        $this->authorize('viewAny', Position::class);
        $position = $this->positionForSelectedTenant($positionId);
        $this->authorize('view', $position);
        $structure = TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->where('position_id', $position->id)->firstOrFail();
        $this->editingPositionId = $position->id;
        $this->parentPositionId = (string) ($structure->parent_position_id ?? '');
        $this->sortOrder = (string) $structure->sort_order;
        $this->isRoot = (bool) $structure->is_root;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function saveStructure(): void
    {
        $this->ensureCanManage();
        $position = $this->positionForSelectedTenant((string) $this->editingPositionId);
        $this->validate([
            'selectedTenantId' => ['required', 'uuid', Rule::exists('tenants', 'id')],
            'parentPositionId' => ['nullable', 'string'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
            'isRoot' => ['boolean'],
        ]);

        $parentId = $this->parentPositionId !== '' ? $this->parentPositionId : null;
        if ($parentId !== null) {
            $parent = $this->positionForSelectedTenant($parentId);
            if ((string) $parent->id === (string) $position->id) {
                $this->addError('parentPositionId', 'Jabatan tidak dapat menjadi atasan dirinya sendiri.');
                return;
            }
            if ($this->wouldCreateCycle($position->id, $parent->id)) {
                $this->addError('parentPositionId', 'Struktur tidak boleh membentuk siklus.');
                return;
            }
        }
        if ($this->isRoot && $parentId !== null) {
            $this->addError('parentPositionId', 'Kepala organisasi tidak boleh memiliki atasan.');
            return;
        }

        DB::transaction(function () use ($position, $parentId): void {
            $structure = TenantPositionStructure::query()->firstOrCreate(
                ['tenant_id' => $this->selectedTenantId, 'position_id' => $position->id],
                ['sort_order' => 0, 'is_root' => false]
            );
            if ($this->isRoot) {
                TenantPositionStructure::query()
                    ->where('tenant_id', $this->selectedTenantId)
                    ->where('position_id', '!=', $position->id)
                    ->update(['is_root' => false]);
            }
            $structure->update([
                'parent_position_id' => $parentId,
                'sort_order' => (int) $this->sortOrder,
                'is_root' => $this->isRoot,
            ]);
        });

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Struktur organisasi berhasil diperbarui.');
    }

    public function setRoot(string $positionId): void
    {
        $this->ensureCanManage();
        $position = $this->positionForSelectedTenant($positionId);
        DB::transaction(function () use ($position): void {
            TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->update(['is_root' => false]);
            TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->where('position_id', $position->id)->update(['parent_position_id' => null, 'is_root' => true]);
        });
        $this->dispatch('toast', type: 'success', message: $position->name.' ditetapkan sebagai kepala organisasi.');
    }

    private function ensureCanManage(): void
    {
        abort_unless(auth()->user()?->canManagePositions(), 403);
    }

    private function positionForSelectedTenant(string $id): Position
    {
        $position = Position::query()->with('category')->findOrFail($id);
        abort_unless($this->selectedTenantId !== '', 422);
        $categoryId = Tenant::query()->whereKey($this->selectedTenantId)->value('tenant_category_id');
        abort_unless((string) $position->tenant_category_id === (string) $categoryId, 403);
        $this->authorize('view', $position);
        return $position;
    }

    private function ensureStructureRows(): void
    {
        $categoryId = Tenant::query()->whereKey($this->selectedTenantId)->value('tenant_category_id');
        if (! $categoryId) return;
        $positions = Position::query()->where('tenant_category_id', $categoryId)->where('status', 'active')->get(['id', 'parent_id', 'sort_order']);
        foreach ($positions as $position) {
            TenantPositionStructure::query()->firstOrCreate(
                ['tenant_id' => $this->selectedTenantId, 'position_id' => $position->id],
                ['parent_position_id' => null, 'sort_order' => $position->sort_order, 'is_root' => false]
            );
        }
    }

    private function wouldCreateCycle(string $positionId, string $parentId): bool
    {
        $rows = TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->pluck('parent_position_id', 'position_id');
        $cursor = $parentId;
        $seen = [];
        while ($cursor !== '') {
            if ($cursor === $positionId) return true;
            if (isset($seen[$cursor])) return true;
            $seen[$cursor] = true;
            $cursor = (string) ($rows[$cursor] ?? '');
        }
        return false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingPositionId', 'parentPositionId', 'sortOrder', 'isRoot', 'showForm']);
        $this->sortOrder = '0';
        $this->resetValidation();
    }

    public function render()
    {
        $user = auth()->user();
        $tenants = $user->isSuperAdmin()
            ? Tenant::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'tenant_category_id'])
            : Tenant::query()->whereKey($user->tenant_id)->get(['id', 'name', 'tenant_category_id']);

        $categoryId = $this->selectedTenantId ? Tenant::query()->whereKey($this->selectedTenantId)->value('tenant_category_id') : null;
        $positions = $categoryId
            ? Position::query()->with(['holders' => fn ($q) => $q->where('tenant_id', $this->selectedTenantId)->whereNull('ended_at')->with('user')])->where('tenant_category_id', $categoryId)->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get()
            : collect();
        $structures = $this->selectedTenantId
            ? TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->get()->keyBy('position_id')
            : collect();

        $nodes = $positions->map(fn (Position $position) => [
            'position' => $position,
            'structure' => $structures->get($position->id),
        ]);
        $roots = $nodes->filter(fn ($node) => $node['structure']?->is_root || $node['structure']?->parent_position_id === null)->values();
        if ($roots->count() > 1) {
            $explicitRoot = $nodes->first(fn ($node) => $node['structure']?->is_root);
            if ($explicitRoot) $roots = collect([$explicitRoot]);
        }

        return view('livewire.positions.structure', [
            'tenants' => $tenants,
            'positions' => $positions,
            'structures' => $structures,
            'nodes' => $nodes,
            'roots' => $roots,
            'canManage' => $user->canManagePositions(),
            'positionTypes' => PositionType::cases(),
        ]);
    }
}
