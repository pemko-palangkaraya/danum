<?php

declare(strict_types=1);

namespace App\Livewire\Positions;

use App\Enums\PositionStatus;
use App\Models\Position;
use App\Services\PositionCertificateService;
use App\Services\PositionHolderService;
use App\Services\PositionIndexService;
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
    public string $holderAssignmentStatus = 'definitif';
    public bool $showHistory = false;
    public ?string $historyPositionId = null;
    public string $historyPositionName = '';
    public bool $showCertificate = false;
    public ?string $certificatePositionId = null;
    public string $certificatePositionName = '';
    public string $certificateHolderName = '';

    public function mount(PositionIndexService $index): void
    {
        $this->authorize('viewAny', Position::class);
        $user = auth()->user();
        if ($user->tenant_id) $this->selectedTenantId = (string) $index->categoryIdFor($user);
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }
    public function updatedSelectedTenantId(): void { $this->resetPage(); }
    public function create(): void { $this->authorize('create', Position::class); $this->resetForm(); $this->showForm = true; }

    public function edit(string $id): void
    {
        $position = Position::query()->findOrFail($id); $this->authorize('update', $position);
        $this->editingId = $position->id; $this->selectedTenantId = (string) $position->tenant_category_id; $this->code = $position->code;
        $this->name = $position->name; $this->description = (string) $position->description; $this->status = $position->status->value;
        $this->can_sign = (bool) $position->can_sign; $this->can_validate = (bool) $position->can_validate; $this->resetValidation(); $this->showForm = true;
    }

    public function save(PositionService $service, PositionIndexService $index): void
    {
        $user = auth()->user(); $position = $this->editingId ? Position::query()->findOrFail($this->editingId) : null;
        $this->authorize($position ? 'update' : 'create', $position ?? Position::class);
        $categoryId = $user->tenant_id ? (string) $index->categoryIdFor($user) : $this->selectedTenantId;
        if ($user->tenant_id) $this->selectedTenantId = $categoryId;
        $this->validate([
            'selectedTenantId' => ['required', 'string', Rule::exists('tenant_categories', 'id')->where(fn ($q) => $q->where('is_active', true))],
            'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->where(fn ($q) => $q->where('tenant_category_id', $categoryId))->ignore($this->editingId)->whereNull('deleted_at')],
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'status' => ['required', Rule::enum(PositionStatus::class)],
            'can_sign' => ['boolean'], 'can_validate' => ['boolean'],
        ], ['selectedTenantId.required' => 'Kategori tenant wajib dipilih.', 'selectedTenantId.exists' => 'Kategori tenant yang dipilih tidak valid.', 'code.unique' => 'Kode jabatan sudah digunakan pada kategori tenant ini.']);
        $data = ['tenant_category_id' => $categoryId, 'code' => trim($this->code), 'name' => trim($this->name), 'description' => $this->description ?: null, 'status' => $this->status, 'can_sign' => $this->can_sign, 'can_validate' => $this->can_validate];
        if ($position) { $service->update($position, $data); $message = 'Jabatan berhasil diperbarui.'; } else { $service->create($data); $message = 'Jabatan berhasil dibuat.'; }
        $this->showForm = false; $this->resetForm(); $this->dispatch('toast', type: 'success', message: $message);
    }

    public function assignHolder(string $positionId, PositionHolderService $holders): void
    {
        $position = Position::query()->findOrFail($positionId);
        if (! auth()->user()->canManagePositions()) { $this->dispatch('toast', type: 'error', message: 'Anda tidak memiliki izin untuk mengatur pejabat.'); return; }
        $this->authorize('manageHolder', $position); $this->holderPositionId = $position->id;
        $currentHolder = $holders->active($position); $this->holderUserId = $currentHolder?->user_id !== null ? (string) $currentHolder->user_id : '';
        $this->holderStartedAt = $currentHolder?->started_at ? $currentHolder->started_at->toDateString() : now()->toDateString(); $this->holderAssignmentStatus = $currentHolder?->assignment_status ?? 'definitif';
        $this->resetValidation(['holderUserId', 'holderStartedAt', 'holderAssignmentStatus']); $this->showHolderForm = true;
    }

    public function saveHolder(PositionHolderService $holders): void
    {
        if (! auth()->user()->canManagePositions()) { $this->dispatch('toast', type: 'error', message: 'Anda tidak memiliki izin untuk mengatur pejabat.'); return; }
        $this->validate(['holderUserId' => ['required', 'integer'], 'holderStartedAt' => ['required', 'date'], 'holderAssignmentStatus' => ['required', Rule::in(['definitif', 'plt'])]], ['holderUserId.required' => 'Silakan pilih pejabat.', 'holderStartedAt.required' => 'Tanggal mulai wajib diisi.', 'holderStartedAt.date' => 'Tanggal mulai tidak valid.']);
        $position = Position::query()->findOrFail($this->holderPositionId); $this->authorize('manageHolder', $position);
        try { $holders->assign($position, (int) $this->holderUserId, new \DateTimeImmutable($this->holderStartedAt), $this->holderAssignmentStatus); }
        catch (\Throwable $exception) { $field = str_contains(strtolower($exception->getMessage()), 'start date') ? 'holderStartedAt' : 'holderUserId'; $this->addError($field, $exception->getMessage()); return; }
        $this->showHolderForm = false; $this->resetHolderForm(); $this->dispatch('toast', type: 'success', message: 'Pemegang jabatan berhasil ditetapkan.');
    }

    public function manageCertificate(string $positionId, PositionService $positions, PositionCertificateService $certificates): void
    {
        $position = Position::query()->findOrFail($positionId); $this->authorize('manageHolder', $position);
        try { $holder = $certificates->validateSigner($position, $positions); } catch (\Throwable $exception) { $this->dispatch('toast', type: 'error', message: $exception->getMessage()); return; }
        $this->certificatePositionId = $position->id; $this->certificatePositionName = $position->name; $this->certificateHolderName = $holder->user->name; $this->showCertificate = true;
    }

    public function generateCertificate(SignerCertificateService $service, PositionService $positions): void
    {
        $position = Position::query()->findOrFail($this->certificatePositionId); $this->authorize('manageHolder', $position); $holder = $positions->getActiveHolder($position); $holder?->loadMissing('user');
        if (! $holder) { $this->addError('certificatePositionId', 'Tetapkan pejabat aktif terlebih dahulu.'); return; }
        try { $service->generate($position, $holder, auth()->user()); } catch (\Throwable $exception) { $this->addError('certificatePositionId', $exception->getMessage()); return; }
        $this->dispatch('toast', type: 'success', message: 'Sertifikat publik berhasil dibuat.'); $this->showCertificate = false; $this->resetCertificateForm();
    }

    public function downloadCertificate(string $positionId, PositionCertificateService $certificates)
    {
        $position = Position::query()->findOrFail($positionId); $this->authorize('view', $position); ['certificate' => $certificate, 'filename' => $filename] = $certificates->download($position);
        return response()->streamDownload(fn () => print($certificate->certificate_pem), $filename, ['Content-Type' => 'application/x-pem-file']);
    }

    public function showHistoryFor(string $positionId): void
    {
        $position = Position::query()->findOrFail($positionId); $this->authorize('view', $position); $this->historyPositionId = $position->id; $this->historyPositionName = $position->name; $this->showHistory = true;
    }

    public function endHolder(string $positionId, PositionHolderService $holders): void
    {
        $position = Position::query()->findOrFail($positionId); $this->authorize('manageHolder', $position); $holder = $holders->active($position); if ($holder) $holders->end($holder, now()); $this->dispatch('toast', type: 'success', message: 'Pemegang jabatan berhasil diakhiri.');
    }

    public function toggleStatus(string $id, PositionService $service): void
    {
        $position = Position::query()->findOrFail($id); $this->authorize('update', $position); $next = $position->status === PositionStatus::ACTIVE ? PositionStatus::INACTIVE : PositionStatus::ACTIVE; $service->update($position, ['status' => $next->value]); $this->dispatch('toast', type: 'success', message: 'Status jabatan diperbarui.');
    }

    private function resetForm(): void { $this->reset(['editingId', 'code', 'name', 'description', 'can_sign', 'can_validate']); if (! auth()->user()->tenant_id) $this->selectedTenantId = ''; $this->status = PositionStatus::ACTIVE->value; $this->resetValidation(); }
    private function resetHolderForm(): void { $this->reset(['holderPositionId', 'holderUserId', 'holderStartedAt', 'holderAssignmentStatus']); $this->holderAssignmentStatus = 'definitif'; }
    private function resetCertificateForm(): void { $this->reset(['certificatePositionId', 'certificatePositionName', 'certificateHolderName']); $this->resetValidation(); }

    public function render(PositionIndexService $index)
    {
        $user = auth()->user(); $categoryId = $index->categoryIdFor($user, $this->selectedTenantId); $positions = $index->positions($user, $categoryId, $this->search, $this->filter, $this->perPage); $users = $index->holderUsers($user, $categoryId); $history = $index->history($this->historyPositionId, $user); $certificate = $this->showCertificate ? $index->certificate($this->certificatePositionId) : null; $categories = $index->categories(); $index->preparePositions($positions, $user);
        return view('livewire.pages.positions.index', ['positions' => $positions, 'users' => $users, 'history' => $history, 'certificate' => $certificate, 'tenants' => $categories, 'isSuperAdmin' => $user->isSuperAdmin(), 'canManageHolders' => $user->canManagePositions()]);
    }
}
