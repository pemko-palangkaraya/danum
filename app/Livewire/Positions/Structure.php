<?php

declare(strict_types=1);

namespace App\Livewire\Positions;

use App\Enums\PositionAssignmentStatus;
use App\Enums\PositionType;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PositionService;
use App\Services\PositionStructureService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Structure extends Component
{
    use WithFileUploads;

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
    public string $holderAssignmentStatus = PositionAssignmentStatus::DEFINITIF->value;
    public string $holderAppointmentNumber = '';
    public $holderAppointmentDocument = null;

    public function mount(?string $tenant = null): void
    {
        $this->authorize('viewAny', Position::class);
        $user = auth()->user();
        if ($user->tenant_id) $this->selectedTenantId = (string) $user->tenant_id;
        elseif ($user->isSuperAdmin() && $tenant) $this->selectedTenantId = $tenant;
        if ($this->selectedTenantId !== '') app(PositionStructureService::class)->ensureRows($this->selectedTenantId);
    }

    public function updatedSelectedTenantId(PositionStructureService $structures): void
    {
        $this->resetForm();
        $this->resetHolderForm();
        if ($this->selectedTenantId !== '') $structures->ensureRows($this->selectedTenantId);
    }

    public function editStructure(string $positionId, PositionStructureService $structures): void
    {
        $position = $this->positionForSelectedTenant($positionId, $structures);
        $structure = $structures->structureForPosition($this->selectedTenantId, $position->id);
        $this->editingPositionId = $position->id;
        $this->parentPositionId = (string) ($structure->parent_position_id ?? '');
        $this->sortOrder = (string) $structure->sort_order;
        $this->positionType = $position->position_type?->value ?? PositionType::MANAGERIAL->value;
        $this->isRoot = (bool) $structure->is_root;
        $this->showForm = true;
        $this->resetValidation();
    }

    public function saveStructure(PositionStructureService $structures): void
    {
        $this->ensureCanManage();
        $position = $this->positionForSelectedTenant((string) $this->editingPositionId, $structures);
        $this->validate([
            'selectedTenantId' => ['required', 'uuid', Rule::exists('tenants', 'id')],
            'parentPositionId' => ['nullable', 'string'],
            'sortOrder' => ['required', 'integer', 'min:0', 'max:9999'],
            'positionType' => ['required', Rule::enum(PositionType::class)],
            'isRoot' => ['boolean'],
        ]);
        $parentId = $this->parentPositionId !== '' ? $this->parentPositionId : null;
        try {
            $structures->save($this->selectedTenantId, $position, $parentId, (int) $this->sortOrder, $this->positionType, $this->isRoot);
        } catch (\InvalidArgumentException $exception) {
            $this->addError('parentPositionId', $exception->getMessage());
            return;
        }
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Struktur dan jenis jabatan berhasil diperbarui.');
    }

    public function openHolderForm(string $positionId, PositionStructureService $structures): void
    {
        $this->ensureCanManage();
        $position = $this->positionForSelectedTenant($positionId, $structures);
        $current = $position->holders()->where('tenant_id', $this->selectedTenantId)->whereNull('ended_at')->latest('started_at')->first();
        $this->holderPositionId = $position->id;
        $this->holderUserId = (string) ($current?->user_id ?? '');
        $this->holderStartedAt = $current?->started_at?->toDateString() ?? now()->toDateString();
        $this->holderAssignmentStatus = $current?->assignment_status ?? PositionAssignmentStatus::DEFINITIF->value;
        $this->holderAppointmentNumber = $current?->appointment_number ?? '';
        $this->holderAppointmentDocument = null;
        $this->showHolderForm = true;
        $this->resetValidation();
    }

    public function saveHolder(PositionService $service, PositionStructureService $structures): void
    {
        $this->ensureCanManage();
        $this->validate([
            'holderUserId' => ['required', 'integer'],
            'holderStartedAt' => ['required', 'date'],
            'holderAssignmentStatus' => ['required', Rule::enum(PositionAssignmentStatus::class)],
            'holderAppointmentNumber' => ['required', 'string', 'max:255'],
            'holderAppointmentDocument' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'holderAppointmentNumber.required' => 'Nomor SK wajib diisi.',
            'holderAppointmentDocument.mimes' => 'Dokumen SK harus berupa PDF.',
            'holderAppointmentDocument.max' => 'Ukuran dokumen SK maksimal 10 MB.',
        ]);

        $position = $this->positionForSelectedTenant((string) $this->holderPositionId, $structures);
        $documentPath = null;
        try {
            if ($this->holderAppointmentDocument) {
                $documentPath = $this->holderAppointmentDocument->store('position-appointments/' . $this->selectedTenantId . '/' . $position->id, 'local');
            }
            $service->assignHolder($position, (int) $this->holderUserId, new \DateTimeImmutable($this->holderStartedAt), PositionAssignmentStatus::from($this->holderAssignmentStatus), $this->holderAppointmentNumber, $documentPath);
        } catch (\Throwable $exception) {
            if ($documentPath) Storage::disk('local')->delete($documentPath);
            $this->addError('holderUserId', $exception->getMessage());
            return;
        }
        $this->showHolderForm = false;
        $this->resetHolderForm();
        $this->dispatch('toast', type: 'success', message: 'Pemangku jabatan dan SK berhasil ditetapkan.');
    }

    public function setRoot(string $positionId, PositionStructureService $structures): void
    {
        $this->ensureCanManage();
        $position = $this->positionForSelectedTenant($positionId, $structures);
        $structures->setRoot($this->selectedTenantId, $position);
        $this->dispatch('toast', type: 'success', message: $position->name.' ditetapkan sebagai kepala organisasi.');
    }

    private function ensureCanManage(): void { abort_unless(auth()->user()?->canManagePositions(), 403); }

    private function positionForSelectedTenant(string $id, PositionStructureService $structures): Position
    {
        abort_unless($this->selectedTenantId !== '', 422);
        $position = $structures->positionForTenant($this->selectedTenantId, $id);
        $this->authorize('view', $position);
        return $position;
    }

    private function resetForm(): void
    {
        $this->reset(['editingPositionId', 'parentPositionId', 'sortOrder', 'positionType', 'isRoot', 'showForm']);
        $this->sortOrder = '0'; $this->positionType = PositionType::MANAGERIAL->value; $this->resetValidation();
    }

    private function resetHolderForm(): void
    {
        $this->reset(['holderPositionId', 'holderUserId', 'holderStartedAt', 'holderAssignmentStatus', 'holderAppointmentNumber', 'holderAppointmentDocument', 'showHolderForm']);
        $this->holderAssignmentStatus = PositionAssignmentStatus::DEFINITIF->value;
    }

    public function render(PositionStructureService $structures)
    {
        $user = auth()->user();
        $tenants = $user->isSuperAdmin() ? Tenant::query()->where('status', true)->orderBy('name')->get(['id', 'name', 'tenant_category_id']) : Tenant::query()->whereKey($user->tenant_id)->get(['id', 'name', 'tenant_category_id']);
        $categoryId = $this->selectedTenantId ? $structures->tenantCategoryId($this->selectedTenantId) : null;
        $positions = $categoryId ? Position::query()->with(['holders' => fn ($q) => $q->where('tenant_id', $this->selectedTenantId)->whereNull('ended_at')->where('started_at', '<=', now())->with('user')])->where('tenant_category_id', $categoryId)->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get() : collect();
        $structures = $this->selectedTenantId ? $structures->structures($this->selectedTenantId) : collect();
        $nodes = $positions->map(fn (Position $position) => ['position' => $position, 'structure' => $structures->get($position->id)]);
        $roots = $nodes->filter(fn ($node) => $node['structure']?->is_root || $node['structure']?->parent_position_id === null)->values();
        if ($roots->count() > 1) { $explicitRoot = $nodes->first(fn ($node) => $node['structure']?->is_root); if ($explicitRoot) $roots = collect([$explicitRoot]); }
        $users = $this->selectedTenantId ? User::query()->where('tenant_id', $this->selectedTenantId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']) : collect();
        return view('livewire.positions.structure', compact('tenants', 'positions', 'structures', 'nodes', 'roots', 'users') + ['canManage' => $user->canManagePositions(), 'positionTypes' => PositionType::cases(), 'assignmentStatuses' => PositionAssignmentStatus::cases()]);
    }
}
