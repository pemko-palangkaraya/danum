<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Livewire\Concerns\HandlesLetterVariables;
use App\Livewire\Concerns\WithStandardTablePagination;
use App\Models\OutgoingLetter;
use App\Services\DocxTemplateService;
use App\Services\LetterTypeService;
use App\Services\OutgoingLetterDraftService;
use App\Services\OutgoingLetterPositionService;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterWorkflowService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use HandlesLetterVariables;
    use WithStandardTablePagination;

    public string $search = '';
    public string $filter = 'all';
    public int $perPage = 5;
    public bool $showForm = false;
    public ?string $editingId = null;
    public bool $showRejectForm = false;
    public string $rejectId = '';
    public string $rejectReason = '';
    public string $letter_type_id = '';
    public string $signer_position_id = '';
    public string $validator_position_id = '';
    public array $variables = [];
    public array $variableValues = [];

    public function mount(): void { $this->filter = 'all'; }
    public function updatedPerPage(): void { $this->perPage = max(5, min($this->perPage, 50)); $this->resetPage('issuedPage'); }

    public function create(): void
    {
        try { $this->authorize('create', OutgoingLetter::class); $this->resetForm(); $this->showForm = true; }
        catch (\Throwable) { $this->toastError('Anda tidak memiliki izin untuk membuat surat.'); }
    }

    public function edit(string $id): void
    {
        try {
            $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('update', $letter);
            $letterType = $letter->letterType; $tenantId = auth()->user()->tenant_id;
            if (! $letterType || $tenantId === null || ! app(LetterTypeService::class)->isAllowedForTenant($letterType, $tenantId)) throw new \DomainException('Jenis surat ini sudah tidak diberikan akses ke OPD Anda.');
            $this->editingId = $letter->id; $this->letter_type_id = $letter->letter_type_id; $this->signer_position_id = $letter->signer_position_id; $this->validator_position_id = $letter->validator_position_id;
            $version = app(LetterTypeService::class)->activeVersion($letterType); $this->variables = $version?->variables ?? $letterType->variables ?? []; $this->variableValues = $letter->input_data ?? []; $this->initializeVariableValues();
            $this->variableValues['number'] = $letter->number; $this->variableValues['recipient_name'] = $letter->recipient_name; $this->variableValues['recipient_address'] = $letter->recipient_address; $this->variableValues['subject'] = $letter->subject; $this->variableValues['date'] = optional($letter->letter_date)->format('Y-m-d');
            $this->applySystemValues(); $this->resetValidation(); $this->showForm = true;
        } catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Draft tidak dapat diedit.'); }
    }

    public function updatedLetterTypeId(): void
    {
        $tenantId = auth()->user()->tenant_id;
        if ($tenantId === null) { $this->letter_type_id = ''; $this->variables = []; $this->variableValues = []; return; }
        $service = app(LetterTypeService::class); $type = $service->getAvailableForTenant($tenantId)->firstWhere('id', $this->letter_type_id);
        if (! $type) { $this->letter_type_id = ''; $this->variables = []; $this->variableValues = []; $this->toastError('Jenis surat tersebut belum diberikan akses ke OPD Anda.'); return; }
        $version = $service->activeVersion($type); $this->variables = $version?->variables ?? $type->variables ?? []; $this->variableValues = []; $this->initializeVariableValues(true); $this->applySystemValues();
    }

    #[On('outgoing-letters-refresh')]
    public function refreshForRealtime(): void { if ($this->showForm || $this->showRejectForm) $this->skipRender(); }

    public function save(OutgoingLetterDraftService $drafts, OutgoingLetterPositionService $positions): void
    {
        try {
            $tenant = auth()->user()->tenant; $tenantId = auth()->user()->tenant_id;
            if ($tenantId === null || ! $tenant) throw new \DomainException('Akun platform tidak dapat membuat surat tanpa konteks tenant.');
            $this->resetValidation(); $this->validate(['letter_type_id' => ['required', Rule::exists('letter_types', 'id')->where('status', LetterTypeStatus::ACTIVE->value)], 'signer_position_id' => ['required', 'uuid'], 'validator_position_id' => ['required', 'uuid'], 'variableValues' => ['array']]);
            $letterTypeService = app(LetterTypeService::class); $letterType = $letterTypeService->getAvailableForTenant($tenantId)->firstWhere('id', $this->letter_type_id);
            if (! $letterType) throw new \DomainException('Jenis surat tidak tersedia atau belum diberikan akses ke OPD Anda.');
            $position = $positions->availableForTenantCategory($tenantId, $tenant->tenant_category_id, 'can_sign')->find($this->signer_position_id); $holder = $position?->holders->first();
            if (! $position || ! $holder?->user) throw new \DomainException('Jabatan penanda tangan tidak tersedia atau belum memiliki pejabat aktif.');
            $validatorPosition = $positions->availableForTenantCategory($tenantId, $tenant->tenant_category_id, 'can_validate')->find($this->validator_position_id); $validatorHolder = $validatorPosition?->holders->first();
            if (! $validatorPosition || ! $validatorHolder?->user) throw new \DomainException('Jabatan verifikator tidak tersedia atau belum memiliki pejabat aktif.');
            $this->applySystemValues($holder); $this->validateVariableValues(); if ($this->getErrorBag()->isNotEmpty()) return;
            $existing = $this->editingId ? $this->tenantQuery()->findOrFail($this->editingId) : null; if ($existing) $this->authorize('update', $existing);
            $message = $drafts->save($existing, $letterType, $position, $holder, $validatorPosition, $validatorHolder, $this->normalizedVariableValues(), auth()->id(), $tenantId, $tenant);
            $this->showForm = false; $this->resetForm(); $this->dispatch('toast', type: 'success', message: $message);
        } catch (ValidationException $exception) { $this->setErrorBag($exception->validator->errors()); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal disimpan. Silakan coba lagi.'); }
    }

    public function submitLetter(string $id, OutgoingLetterWorkflowService $workflow): void
    {
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('submit', $letter); $workflow->submit($letter, auth()->id()); $this->dispatch('toast', type: 'success', message: 'Surat dikirim untuk verifikasi. Data sekarang terkunci.'); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal dikirim untuk verifikasi.'); }
    }

    public function openReject(string $id): void
    {
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('reject', $letter); $this->rejectId = $id; $this->rejectReason = ''; $this->resetValidation(); $this->showRejectForm = true; }
        catch (\Throwable) { $this->toastError('Anda tidak memiliki kewenangan untuk menolak surat ini.'); }
    }

    public function rejectLetter(OutgoingLetterWorkflowService $workflow): void
    {
        try { $this->validate(['rejectReason' => ['required', 'string', 'max:2000']]); $letter = $this->tenantQuery()->findOrFail($this->rejectId); $this->authorize('reject', $letter); $workflow->reject($letter, auth()->id(), $this->rejectReason); $this->showRejectForm = false; $this->rejectId = ''; $this->rejectReason = ''; $this->dispatch('toast', type: 'success', message: 'Surat ditolak dan dikembalikan kepada pembuat beserta alasan penolakan.'); }
        catch (ValidationException $exception) { $this->setErrorBag($exception->validator->errors()); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal ditolak.'); }
    }

    public function validateLetter(string $id, OutgoingLetterWorkflowService $workflow, ?string $note = null): void
    {
        if (blank($note)) { $this->dispatch('workflow-note-required', action: 'validate', id: $id, title: 'Catatan Verifikasi', description: 'Berikan catatan pemeriksaan sebelum mengesahkan bahwa surat ini sudah diverifikasi.'); return; }
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('validate', $letter); $workflow->validate($letter, auth()->id(), $note); $this->dispatch('toast', type: 'success', message: 'Surat berhasil diverifikasi.'); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal diverifikasi. Anda mungkin tidak memiliki kewenangan atau status surat sudah berubah.'); }
    }

    public function issue(string $id, OutgoingLetterService $service, ?string $note = null, ?string $pin = null): void
    {
        if (blank($note)) { $this->dispatch('workflow-note-required', action: 'issue', id: $id, title: 'Catatan Penerbitan', description: 'Periksa PDF surat, lalu berikan catatan sebelum menerbitkan surat.'); return; }
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('issue', $letter); $this->dispatch('issue-review-required', id: $letter->id, note: trim($note), pdfUrl: route('outgoing-letters.pdf', ['id' => $letter->id])); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat tidak dapat dipersiapkan untuk diterbitkan.'); }
    }

    #[On('signer-pin-submitted')]
    public function handleSignerPin(string $action, string $id, string $note, string $pin, OutgoingLetterService $service): void
    {
        if ($action !== 'issue') return;
        $pin = trim($pin); if (! preg_match('/^\d{6}$/', $pin)) { $this->dispatch('signer-pin-invalid'); return; }
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('issue', $letter); $service->issue($letter, auth()->id(), trim($note), $pin, true); $this->dispatch('toast', type: 'success', message: 'Surat berhasil diterbitkan dan ditandatangani secara elektronik.'); }
        catch (\Throwable $exception) { report($exception); $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'TTE gagal diproses. Silakan coba lagi.'); }
    }

    public function restoreLetter(string $id, OutgoingLetterService $service): void
    {
        try { $letter = OutgoingLetter::withTrashed()->findOrFail($id); $this->authorize('restore', $letter); $service->restore($letter); $this->dispatch('toast', type: 'success', message: 'Surat berhasil direstore.'); $this->resetPage(); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal direstore.'); }
    }

    private function isSuperAdmin(): bool { return auth()->user()->isSuperAdmin(); }
    private function tenantQuery() { return $this->isSuperAdmin() ? OutgoingLetter::query()->with(['letterType']) : OutgoingLetter::query()->where('tenant_id', auth()->user()->tenant_id)->with(['letterType']); }
    private function archiveQuery() { return $this->isSuperAdmin() ? OutgoingLetter::withTrashed() : $this->tenantQuery(); }
    private function resetForm(): void { $this->reset(['editingId', 'letter_type_id', 'signer_position_id', 'validator_position_id', 'variables', 'variableValues']); }
    private function toastError(string $message): void { $this->dispatch('toast', type: 'error', message: $message); }

    public function render(OutgoingLetterPositionService $positions)
    {
        $this->filter = $this->filter ?: 'all'; $this->authorize('viewAny', OutgoingLetter::class);
        $letters = $this->archiveQuery()->with(['tenant', 'letterType', 'letterTypeVersion', 'signerPosition', 'signerUser', 'validatorPosition', 'validatorUser', 'creator', 'rejectedBy'])->latest();
        if ($this->isSuperAdmin() && $this->filter === 'deleted') $letters->onlyTrashed(); elseif ($this->filter !== 'all') $letters->where('status', $this->filter);
        if ($this->search !== '') $letters->where(fn ($query) => $query->where('number', 'like', "%{$this->search}%")->orWhere('recipient_name', 'like', "%{$this->search}%")->orWhere('subject', 'like', "%{$this->search}%"));
        $tenantId = auth()->user()->tenant_id; $letterTypeService = app(LetterTypeService::class); $letterTypes = $this->isSuperAdmin() || $tenantId === null ? collect() : $letterTypeService->getAvailableForTenant($tenantId)->sortBy('name')->values();
        $tenant = auth()->user()?->tenant; $signerPositions = $tenant ? $positions->availableForTenantCategory($tenant->id, $tenant->tenant_category_id, 'can_sign')->orderBy('name')->get() : collect(); $validatorPositions = $tenant ? $positions->availableForTenantCategory($tenant->id, $tenant->tenant_category_id, 'can_validate')->orderBy('name')->get() : collect();
        return view('livewire.pages.outgoing-letters.index', ['letters' => $letters->paginate($this->perPage), 'letterTypes' => $letterTypes, 'signerPositions' => $signerPositions, 'validatorPositions' => $validatorPositions, 'variableLabels' => (new DocxTemplateService)->allowedVariables(), 'repeaters' => $this->repeaterDefinitions(), 'isSuperAdmin' => $this->isSuperAdmin()]);
    }
}
