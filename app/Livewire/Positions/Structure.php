<?php

declare(strict_types=1);

namespace App\Livewire\Positions;

use App\Enums\PositionType;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\TenantPositionStructure;
use App\Models\User;
use App\Services\PositionService;
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
    public string $positionType = 'managerial';
    public bool $isRoot = false;
    public bool $showForm = false;
    public bool $showHolderForm = false;
    public ?string $holderPositionId = null;
    public string $holderUserId = '';
    public string $holderStartedAt = '';
    public string $holderAssignmentStatus = 'definitif';

    public function mount(?string $tenant = null): void
    {
        $this->authorize('viewAny', Position::class);
        $user = auth()->user();
        if ($user->tenant_id) $this->selectedTenantId = (string) $user->tenant_id;
        elseif ($user->isSuperAdmin() && $tenant) $this->selectedTenantId = $tenant;
        if ($this->selectedTenantId !== '') $this->ensureStructureRows();
    }

    public function updatedSelectedTenantId(): void
    {
        $this->resetForm();
        $this->resetHolderForm();
        if ($this->selectedTenantId !== '') $this->ensureStructureRows();
    }

    public function editStructure(string $positionId): void
    {
        $position = $this->positionForSelectedTenant($positionId);
        $structure = TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->where('position_id', $position->id)->firstOrFail();
        $this->editingPositionId = $position->id;
        $this->parentPositionId = (string) ($structure->parent_position_id ?? '');
        $this->sortOrder = (string) $structure->sort_order;
        $this->positionType = $position->position_type?->value ?? PositionType::MANAGERIAL->value;
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
            'positionType' => ['required', Rule::enum(PositionType::class)],
            'isRoot' => ['boolean'],
        ]);
        $parentId = $this->parentPositionId !== '' ? $this->parentPositionId : null;
        if ($parentId !== null) {
            $parent = $this->positionForSelectedTenant($parentId);
            if ((string) $parent->id === (string) $position->id) { $this->addError('parentPositionId', 'Jabatan tidak dapat menjadi atasan dirinya sendiri.'); return; }
            if ($this->wouldCreateCycle($position->id, $parent->id)) { $this->addError('parentPositionId', 'Struktur tidak boleh membentuk siklus.'); return; }
        }
        if ($this->isRoot && $parentId !== null) { $this->addError('parentPositionId', 'Kepala organisasi tidak boleh memiliki atasan.'); return; }
        DB::transaction(function () use ($position, $parentId): void {
            $structure = TenantPositionStructure::query()->firstOrCreate(['tenant_id' => $this->selectedTenantId, 'position_id' => $position->id], ['sort_order' => 0, 'is_root' => false]);
            if ($this->isRoot) TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->where('position_id', '!=', $position->id)->update(['is_root' => false]);
            $position->update(['position_type' => $this->positionType]);
            $structure->update(['parent_position_id' => $parentId, 'sort_order' => (int) $this->sortOrder, 'is_root' => $this->isRoot]);
        });
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Struktur dan jenis jabatan berhasil diperbarui.');
    }

    public function openHolderForm(string $positionId): void
    {
        $this->ensureCanManage();
        $position = $this->positionForSelectedTenant($positionId);
        $current = $position->holders()->where('tenant_id', $this->selectedTenantId)->whereNull('ended_at')->latest('started_at')->first();
        $this->holderPositionId = $position->id;
        $this->holderUserId = (string) ($current?->user_id ?? '');
        $this->holderStartedAt = $current?->started_at?->toDateString() ?? now()->toDateString();
        $this->holderAssignmentStatus = $current?->assignment_status ?? 'definitif';
        $this->showHolderForm = true;
        $this->resetValidation();
    }

    public function saveHolder(PositionService $service): void
    {
        $this->ensureCanManage();
        $this->validate([
            'holderUserId' => ['required', 'integer'],
            'holderStartedAt' => ['required', 'date'],
            'holderAssignmentStatus' => ['required', Rule::in(['definitif', 'plt'])],
        ]);
        $position = $this->positionForSelectedTenant((string) $this->holderPositionId);
        try {
            $service->assignHolder($position, (int) $this->holderUserId, new \DateTimeImmutable($this->holderStartedAt), $this->holderAssignmentStatus);
        } catch (\Throwable $exception) {
            $this->addError('holderUserId', $exception->getMessage());
            return;
        }
        $this->showHolderForm = false;
        $this->resetHolderForm();
        $this->dispatch('toast', type: 'success', message: 'Pemangku jabatan berhasil ditetapkan.');
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

    private function ensureCanManage(): void { abort_unless(auth()->user()?->canManagePositions(), 403); }

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
        $positions = Position::query()->where('tenant_category_id', $categoryId)->where('status', 'active')->get(['id', 'sort_order']);
        foreach ($positions as $position) TenantPositionStructure::query()->firstOrCreate(['tenant_id' => $this->selectedTenantId, 'position_id' => $position->id], ['parent_position_id' => null, 'sort_order' => $position->sort_order, 'is_root' => false]);
    }

    private function wouldCreateCycle(string $positionId, string $parentId): bool
    {
        $rows = TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->pluck('parent_position_id', 'position_id');
        $cursor = $parentId; $seen = [];
        while ($cursor !== '') {
            if ($cursor === $positionId || isset($seen[$cursor])) return true;
            $seen[$cursor] = true; $cursor = (string) ($rows[$cursor] ?? '');
        }
        return false;
    }

    private function resetForm(): void
    {
        $this->reset(['editingPositionId', 'parentPositionId', 'sortOrder', 'positionType', 'isRoot', 'showForm']);
        $this->sortOrder = '0'; $this->positionType = PositionType::MANAGERIAL->value; $this->resetValidation();
    }

    private function resetHolderForm(): void
    {
        $this->reset(['holderPositionId', 'holderUserId', 'holderStartedAt', 'holderAssignmentStatus', 'showHolderForm']);
        $this->holderAssignmentStatus = 'definitif';
    }

    public function render()
    {
        $user = auth()->user();
        $tenants = $user->isSuperAdmin() ? Tenant::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'tenant_category_id']) : Tenant::query()->whereKey($user->tenant_id)->get(['id', 'name', 'tenant_category_id']);
        $categoryId = $this->selectedTenantId ? Tenant::query()->whereKey($this->selectedTenantId)->value('tenant_category_id') : null;
        $positions = $categoryId ? Position::query()->with(['holders' => fn ($q) => $q->where('tenant_id', $this->selectedTenantId)->whereNull('ended_at')->where('started_at', '<=', now())->with('user')])->where('tenant_category_id', $categoryId)->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get() : collect();
        $structures = $this->selectedTenantId ? TenantPositionStructure::query()->where('tenant_id', $this->selectedTenantId)->get()->keyBy('position_id') : collect();
        $nodes = $positions->map(fn (Position $position) => ['position' => $position, 'structure' => $structures->get($position->id)]);
        $roots = $nodes->filter(fn ($node) => $node['structure']?->is_root || $node['structure']?->parent_position_id === null)->values();
        if ($roots->count() > 1) { $explicitRoot = $nodes->first(fn ($node) => $node['structure']?->is_root); if ($explicitRoot) $roots = collect([$explicitRoot]); }
        $users = $this->selectedTenantId ? User::query()->where('tenant_id', $this->selectedTenantId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']) : collect();
        return view('livewire.positions.structure', compact('tenants', 'positions', 'structures', 'nodes', 'roots', 'users') + ['canManage' => $user->canManagePositions(), 'positionTypes' => PositionType::cases()]);
    }
}
