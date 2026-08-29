<?php

declare(strict_types=1);

namespace App\Livewire\Positions;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PositionService;
use App\Services\SignerCertificateService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\WithStandardTablePagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithStandardTablePagination;

    public string $search = '';
    public string $filter = 'active';
    public int $perPage = 5;

    public function updatedPerPage(): void
    {
        $this->perPage = max(5, min($this->perPage, 50));
        $this->resetPage();
    }
    public bool $showForm = false;
    public ?string $editingId = null;
    public string $selectedTenantId = '';
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
    public bool $showHistory = false;
    public ?string $historyPositionId = null;
    public string $historyPositionName = '';
    public bool $showCertificate = false;
    public ?string $certificatePositionId = null;
    public string $certificatePositionName = '';
    public string $certificateHolderName = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Position::class);
        $user = auth()->user();
        if ($user->isTenantUser()) $this->selectedTenantId = (string) $user->tenant_id;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }
    public function updatedSelectedTenantId(): void { $this->resetPage(); }

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
        $this->selectedTenantId = (string) $position->tenant_id;
        $this->code = $position->code;
        $this->name = $position->name;
        $this->description = (string) $position->description;
        $this->status = $position->status->value;
        $this->can_sign = (bool) $position->can_sign;
        $this->can_validate = (bool) $position->can_validate;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(PositionService $service): void
    {
        $user = auth()->user();
        $position = $this->editingId ? Position::query()->findOrFail($this->editingId) : null;
        $this->authorize($position ? 'update' : 'create', $position ?? Position::class);
        $tenantId = $this->selectedTenantId ?: (string) $user->tenant_id;
        if ($user->isTenantUser()) $tenantId = (string) $user->tenant_id;

        $this->validate([
            'selectedTenantId' => ['required', 'string', Rule::exists('tenants', 'id')],
            'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->where(fn($query) => $query->where('tenant_id', $tenantId))->ignore($this->editingId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(PositionStatus::class)],
            'can_sign' => ['boolean'],
            'can_validate' => ['boolean'],
        ], ['code.unique' => 'Kode jabatan sudah digunakan pada organisasi ini.']);

        $data = ['tenant_id' => $tenantId, 'code' => trim($this->code), 'name' => trim($this->name), 'description' => $this->description ?: null, 'status' => $this->status, 'can_sign' => $this->can_sign, 'can_validate' => $this->can_validate];
        if ($position) {
            $service->update($position, $data);
            $message = 'Jabatan berhasil diperbarui.';
        } else {
            $service->create($data);
            $message = 'Jabatan berhasil dibuat.';
        }
        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function assignHolder(string $positionId): void
    {
        $position = Position::query()->with(['holders' => fn($query) => $query->whereNull('ended_at')->orderByDesc('started_at')])->findOrFail($positionId);
        if (! auth()->user()->canManagePositions()) {
            $this->dispatch('toast', type: 'error', message: 'Anda tidak memiliki izin untuk mengatur pejabat.');
            return;
        }
        $this->authorize('manageHolder', $position);
        $this->holderPositionId = $position->id;
        $currentHolder = $position->holders->first();
        $this->holderUserId = $currentHolder?->user_id !== null ? (string) $currentHolder->user_id : '';
        $this->holderStartedAt = $currentHolder?->started_at ? $currentHolder->started_at->toDateString() : now()->toDateString();
        $this->resetValidation(['holderUserId', 'holderStartedAt']);
        $this->showHolderForm = true;
    }

    public function saveHolder(PositionService $service): void
    {
        if (! auth()->user()->canManagePositions()) {
            $this->dispatch('toast', type: 'error', message: 'Anda tidak memiliki izin untuk mengatur pejabat.');
            return;
        }
        $this->validate(['holderUserId' => ['required', 'integer'], 'holderStartedAt' => ['required', 'date']], ['holderUserId.required' => 'Silakan pilih pejabat.', 'holderStartedAt.required' => 'Tanggal mulai wajib diisi.', 'holderStartedAt.date' => 'Tanggal mulai tidak valid.']);
        $position = Position::query()->findOrFail($this->holderPositionId);
        $this->authorize('manageHolder', $position);
        try {
            $service->assignHolder($position, (int) $this->holderUserId, new \DateTimeImmutable($this->holderStartedAt));
        } catch (\Throwable $exception) {
            $field = str_contains(strtolower($exception->getMessage()), 'start date') ? 'holderStartedAt' : 'holderUserId';
            $this->addError($field, $exception->getMessage());
            return;
        }
        $this->showHolderForm = false;
        $this->resetHolderForm();
        $this->dispatch('toast', type: 'success', message: 'Pemegang jabatan berhasil ditetapkan.');
    }

    public function manageCertificate(string $positionId): void
    {
        $position = Position::query()->with(['holders.user', 'signerCertificates' => fn($query) => $query->where('is_active', true)->latest('created_at')])->findOrFail($positionId);
        $this->authorize('manageHolder', $position);
        if (! $position->can_sign) {
            $this->dispatch('toast', type: 'error', message: 'Jabatan ini belum diizinkan untuk TTE.');
            return;
        }
        $holder = $position->holders->first(fn($item) => $item->ended_at === null && $item->started_at?->lte(now()));
        if (! $holder?->user) {
            $this->dispatch('toast', type: 'error', message: 'Tetapkan pejabat aktif terlebih dahulu.');
            return;
        }
        $this->certificatePositionId = $position->id;
        $this->certificatePositionName = $position->name;
        $this->certificateHolderName = $holder->user->name;
        $this->showCertificate = true;
    }

    public function generateCertificate(SignerCertificateService $service): void
    {
        $position = Position::query()->with(['tenant', 'holders.user'])->findOrFail($this->certificatePositionId);
        $this->authorize('manageHolder', $position);
        $holder = $position->holders->first(fn($item) => $item->ended_at === null && $item->started_at?->lte(now()));
        if (! $holder) {
            $this->addError('certificatePositionId', 'Tetapkan pejabat aktif terlebih dahulu.');
            return;
        }
        try {
            $service->generate($position, $holder, auth()->user());
        } catch (\Throwable $exception) {
            $this->addError('certificatePositionId', $exception->getMessage());
            return;
        }
        $this->dispatch('toast', type: 'success', message: 'Sertifikat publik berhasil dibuat.');
        $this->showCertificate = false;
        $this->resetCertificateForm();
    }

    public function downloadCertificate(string $positionId)
    {
        $position = Position::query()->with(['holders', 'signerCertificates'])->findOrFail($positionId);
        $this->authorize('view', $position);
        $certificate = $position->signerCertificates()->where('is_active', true)->latest('created_at')->first();
        if (! $certificate) abort(404);
        $filename = 'sertifikat-' . str($position->code)->slug() . '-' . str($certificate->fingerprint_sha256)->substr(0, 12) . '.pem';
        return response()->streamDownload(fn() => print($certificate->certificate_pem), $filename, ['Content-Type' => 'application/x-pem-file']);
    }

    public function showHistoryFor(string $positionId): void
    {
        $position = Position::query()->findOrFail($positionId);
        $this->authorize('view', $position);
        $this->historyPositionId = $position->id;
        $this->historyPositionName = $position->name;
        $this->showHistory = true;
    }

    public function endHolder(string $positionId, PositionService $service): void
    {
        $position = Position::query()->findOrFail($positionId);
        $this->authorize('manageHolder', $position);
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
        if (auth()->user()->isTenantUser()) $this->selectedTenantId = (string) auth()->user()->tenant_id;
        $this->status = PositionStatus::ACTIVE->value;
        $this->resetValidation();
    }
    private function resetHolderForm(): void { $this->reset(['holderPositionId', 'holderUserId', 'holderStartedAt']); }
    private function resetCertificateForm(): void
    {
        $this->reset(['certificatePositionId', 'certificatePositionName', 'certificateHolderName']);
        $this->resetValidation();
    }

    public function render()
    {
        $user = auth()->user();
        $tenantId = $user->isTenantUser() ? $user->tenant_id : ($this->selectedTenantId ?: null);
        $query = Position::query()->with(['tenant', 'holders.user', 'signerCertificates' => fn($q) => $q->where('is_active', true)->latest('created_at')])->orderBy('name');
        if ($tenantId) $query->where('tenant_id', $tenantId);
        elseif (! $user->isSuperAdmin()) $query->whereRaw('1 = 0');
        if ($this->search !== '') $query->where(fn($q) => $q->where('code', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%"));
        if ($this->filter === 'deleted') $query->onlyTrashed();
        elseif ($this->filter !== 'all') $query->where('status', $this->filter);
        $holderTenantId = null;
        if ($this->holderPositionId) $holderTenantId = Position::query()->whereKey($this->holderPositionId)->value('tenant_id');
        $usersTenantId = $holderTenantId ?: $tenantId;
        $users = $usersTenantId ? User::query()->where('tenant_id', $usersTenantId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']) : collect();
        $history = $this->historyPositionId ? Position::withTrashed()->find($this->historyPositionId)?->holders()->with('user')->orderByDesc('started_at')->get() ?? collect() : collect();
        $tenants = $user->isSuperAdmin() ? Tenant::query()->orderBy('name')->get(['id', 'name']) : collect();
        return view('livewire.pages.positions.index', ['positions' => $query->paginate($this->perPage), 'users' => $users, 'history' => $history, 'tenants' => $tenants, 'isSuperAdmin' => $user->isSuperAdmin(), 'canManageHolders' => $user->canManagePositions()]);
    }
}
