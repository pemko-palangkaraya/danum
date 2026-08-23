<?php

declare(strict_types=1);

namespace App\Livewire\Positions;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\User;
use App\Services\PositionService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = 'active';
    public int $perPage = 10;
    public bool $showForm = false;
    public ?string $editingId = null;
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public string $status = 'active';
    public bool $can_sign = false;
    public bool $can_validate = false;

    public bool $showHolderForm = false;
    public ?string $holderPositionId = null;
    public string $holderUserId = '';
    public string $holderStartedAt = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Position::class);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorize('create', Position::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $position = Position::query()->findOrFail($id);
        $this->authorize('update', $position);
        $this->editingId = $position->id;
        $this->code = $position->code;
        $this->name = $position->name;
        $this->description = (string) $position->description;
        $this->status = $position->status->value;
        $this->can_sign = (bool) $position->can_sign;
        $this->can_validate = (bool) $position->can_validate;
        $this->showForm = true;
    }

    public function save(PositionService $service): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(PositionStatus::class)],
            'can_sign' => ['boolean'],
            'can_validate' => ['boolean'],
        ]);

        $position = $this->editingId ? Position::query()->findOrFail($this->editingId) : null;
        $data = [
            'code' => trim($this->code),
            'name' => trim($this->name),
            'description' => $this->description ?: null,
            'status' => $this->status,
            'can_sign' => $this->can_sign,
            'can_validate' => $this->can_validate,
        ];

        if ($position) {
            $this->authorize('update', $position);
            $service->update($position, $data);
            $message = 'Jabatan berhasil diperbarui.';
        } else {
            $this->authorize('create', Position::class);
            $data['tenant_id'] = auth()->user()->tenant_id;
            $service->create($data);
            $message = 'Jabatan berhasil dibuat.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function assignHolder(string $positionId): void
    {
        $position = Position::query()->with(['holders' => fn ($query) => $query->whereNull('ended_at')->orderByDesc('started_at')])->findOrFail($positionId);
        $this->authorize('update', $position);

        $this->holderPositionId = $position->id;

        $currentHolder = $position->holders->first();
        $this->holderUserId = $currentHolder?->user_id !== null
            ? (string) $currentHolder->user_id
            : '';
        $this->holderStartedAt = $currentHolder?->started_at
            ? $currentHolder->started_at->toDateString()
            : now()->toDateString();

        $this->resetValidation(['holderUserId', 'holderStartedAt']);
        $this->showHolderForm = true;
    }

    public function saveHolder(PositionService $service): void
    {
        $this->validate([
            'holderUserId' => ['required', 'integer'],
            'holderStartedAt' => ['required', 'date'],
        ]);

        $position = Position::query()->findOrFail($this->holderPositionId);
        $this->authorize('update', $position);

        try {
            $service->assignHolder($position, (int) $this->holderUserId, new \DateTimeImmutable($this->holderStartedAt));
        } catch (\Throwable $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
            return;
        }

        $this->showHolderForm = false;
        $this->resetHolderForm();
        $this->dispatch('toast', type: 'success', message: 'Pemegang jabatan berhasil ditetapkan.');
    }

    public function endHolder(string $positionId, PositionService $service): void
    {
        $position = Position::query()->findOrFail($positionId);
        $this->authorize('update', $position);
        $holder = $service->getActiveHolder($position);
        if ($holder) $service->endHolder($holder, now());
        $this->dispatch('toast', type: 'success', message: 'Pemegang jabatan berhasil diakhiri.');
    }

    public function toggleStatus(string $id, PositionService $service): void
    {
        $position = Position::query()->findOrFail($id);
        $this->authorize('update', $position);
        $next = $position->status === PositionStatus::ACTIVE ? PositionStatus::INACTIVE : PositionStatus::ACTIVE;
        $service->update($position, ['status' => $next->value]);
        $this->dispatch('toast', type: 'success', message: 'Status jabatan diperbarui.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'description', 'can_sign', 'can_validate']);
        $this->status = PositionStatus::ACTIVE->value;
    }

    private function resetHolderForm(): void
    {
        $this->reset(['holderPositionId', 'holderUserId', 'holderStartedAt']);
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;
        $query = Position::query()->where('tenant_id', $tenantId)->with(['holders.user'])->orderBy('name');
        if ($this->search !== '') $query->where(fn ($q) => $q->where('code', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%"));
        if ($this->filter === 'deleted') $query->onlyTrashed();
        elseif ($this->filter !== 'all') $query->where('status', $this->filter);

        $users = User::query()->where('tenant_id', $tenantId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']);

        return view('livewire.pages.positions.index', [
            'positions' => $query->paginate($this->perPage),
            'users' => $users,
        ]);
    }
}
