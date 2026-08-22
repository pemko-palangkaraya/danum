<?php

declare(strict_types=1);

namespace App\Livewire\LetterTypes;

use App\Enums\LetterTypeStatus;
use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\Tenant;
use App\Services\LetterTypeService;
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
    public int $perPage = 5;
    public bool $showForm = false;
    public ?string $editingId = null;
    public string $tenant_id = '';
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public string $body_template = '';
    public string $status = 'draft';

    public function mount(): void
    {
        $this->authorize('viewAny', LetterType::class);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorize('create', LetterType::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $letterType = $this->findLetterType($id);
        $this->authorize('update', $letterType);
        $this->editingId = $letterType->id;
        $this->tenant_id = $letterType->tenant_id;
        $this->code = $letterType->code;
        $this->name = $letterType->name;
        $this->description = (string) $letterType->description;
        $this->body_template = (string) $letterType->body_template;
        $this->status = $letterType->status->value;
        $this->showForm = true;
    }

    public function save(LetterTypeService $service): void
    {
        $data = $this->validate([
            'tenant_id' => ['required', 'uuid', Rule::exists('tenants', 'id')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'body_template' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,validated,active,retired'],
        ]);

        if ($this->editingId) {
            $letterType = $this->findLetterType($this->editingId);
            $this->authorize('update', $letterType);
            $service->update($letterType, $data);
            $message = 'Jenis surat berhasil diperbarui.';
        } else {
            $this->authorize('create', LetterType::class);
            $service->create($data);
            $message = 'Jenis surat berhasil dibuat.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function delete(string $id, LetterTypeService $service): void
    {
        $letterType = $this->findLetterType($id);
        $this->authorize('delete', $letterType);
        $service->delete($letterType);
        $this->dispatch('toast', type: 'success', message: 'Jenis surat dihapus.');
    }

    private function findLetterType(string $id): LetterType
    {
        return LetterType::query()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'tenant_id', 'code', 'name', 'description', 'body_template']);
        $this->status = LetterTypeStatus::DRAFT->value;
    }

    public function render()
    {
        $query = LetterType::query()->with('tenant')->latest();
        if ($this->search !== '') {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%"));
        }

        if ($this->filter === 'deleted') {
            $query->onlyTrashed();
        } else {
            $query->where('status', LetterTypeStatus::from($this->filter));
        }

        return view('livewire.pages.letter-types.index', [
            'letterTypes' => $query->paginate($this->perPage),
            'tenants' => Tenant::query()->orderBy('name')->get(),
        ]);
    }
}
