<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Enums\PositionStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Services\DocxTemplateService;
use App\Services\DocxTteService;
use App\Services\LetterTypeService;
use App\Services\OutgoingLetterService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Livewire\Concerns\WithStandardTablePagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithStandardTablePagination;

    public string $search = '';
    public string $filter = 'all';
    public int $perPage = 5;

    public function mount(): void
    {
        $this->filter = 'all';
    }

    public function updatedPerPage(): void
    {
        $this->perPage = max(5, min($this->perPage, 50));
        $this->resetPage('issuedPage');
    }
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

    private const SYSTEM_VARIABLES = ['letterhead', 'tenant_name', 'tenant_city', 'tenant_district', 'tenant_village', 'tenant_province', 'tenant_address', 'tenant_phone', 'tenant_email', 'tenant_head_name', 'tenant_head_title', 'tte'];

    public function create(): void
    {
        try {
            $this->authorize('create', OutgoingLetter::class);
            $this->resetForm();
            $this->showForm = true;
        } catch (\Throwable) {
            $this->toastError('Anda tidak memiliki izin untuk membuat surat.');
        }
    }

    public function edit(string $id): void
    {
        try {
            $letter = $this->tenantQuery()->findOrFail($id);
            $this->authorize('update', $letter);
            $letterType = $letter->letterType;
            $tenantId = auth()->user()->tenant_id;
            if (! $letterType || $tenantId === null || ! app(LetterTypeService::class)->isAllowedForTenant($letterType, $tenantId)) throw new \DomainException('Jenis surat ini sudah tidak diberikan akses ke OPD Anda.');
            $this->editingId = $letter->id;
            $this->letter_type_id = $letter->letter_type_id;
            $this->signer_position_id = $letter->signer_position_id;
            $this->validator_position_id = $letter->validator_position_id;
            $version = $letter->letterType ? app(LetterTypeService::class)->activeVersion($letter->letterType) : null;
            $this->variables = $version?->variables ?? $letter->letterType?->variables ?? [];
            $this->variableValues = $letter->input_data ?? [];
            foreach ($this->variables as $variable) $this->variableValues[$variable] ??= '';
            $this->variableValues['number'] = $letter->number;
            $this->variableValues['recipient_name'] = $letter->recipient_name;
            $this->variableValues['recipient_address'] = $letter->recipient_address;
            $this->variableValues['subject'] = $letter->subject;
            $this->variableValues['date'] = optional($letter->letter_date)->format('Y-m-d');
            $this->applySystemValues();
            $this->resetValidation();
            $this->showForm = true;
        } catch (\Throwable $exception) {
            $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Draft tidak dapat diedit.');
        }
    }

    public function updatedLetterTypeId(): void
    {
        $tenantId = auth()->user()->tenant_id;
        if ($tenantId === null) {
            $this->letter_type_id = '';
            $this->variables = [];
            $this->variableValues = [];
            return;
        }
        $type = app(LetterTypeService::class)->getAvailableForTenant($tenantId)->firstWhere('id', $this->letter_type_id);
        if (! $type) {
            $this->letter_type_id = '';
            $this->variables = [];
            $this->variableValues = [];
            $this->toastError('Jenis surat tersebut belum diberikan akses ke OPD Anda.');
            return;
        }
        $version = app(LetterTypeService::class)->activeVersion($type);
        $this->variables = $version?->variables ?? $type->variables ?? [];
        $this->variableValues = array_fill_keys($this->variables, '');
        $this->applySystemValues();
    }

    #[On('outgoing-letters-refresh')]
    public function refreshForRealtime(): void
    {
        if ($this->showForm || $this->showRejectForm) $this->skipRender();
    }

    public function save(OutgoingLetterService $service, DocxTemplateService $docx, DocxTteService $tte): void
    {
        try {
            $tenantId = auth()->user()->tenant_id;
            if ($tenantId === null) throw new \DomainException('Akun platform tidak dapat membuat surat tanpa konteks tenant.');
            $this->resetValidation();
            $this->validate(['letter_type_id' => ['required', Rule::exists('letter_types', 'id')->where('status', LetterTypeStatus::ACTIVE->value)], 'signer_position_id' => ['required', 'uuid'], 'validator_position_id' => ['required', 'uuid'], 'variableValues' => ['array']]);
            $letterType = app(LetterTypeService::class)->getAvailableForTenant($tenantId)->firstWhere('id', $this->letter_type_id);
            if (! $letterType) throw new \DomainException('Jenis surat tidak tersedia atau belum diberikan akses ke OPD Anda.');
            $position = $this->availableSignerPositions()->find($this->signer_position_id);
            $holder = $position?->holders->first();
            if (! $position || ! $holder?->user) throw new \DomainException('Jabatan penanda tangan tidak tersedia atau belum memiliki pejabat aktif.');
            $validatorPosition = $this->availableValidatorPositions()->find($this->validator_position_id);
            $validatorHolder = $validatorPosition?->holders->first();
            if (! $validatorPosition || ! $validatorHolder?->user) throw new \DomainException('Jabatan verifikator tidak tersedia atau belum memiliki pejabat aktif.');
            $this->applySystemValues($holder);
            foreach ($this->variables as $variable) {
                if ($this->isSystemVariable($variable)) continue;
                if (blank($this->variableValues[$variable] ?? null)) $this->addError('variableValues.' . $variable, 'Field ini wajib diisi.');
            }
            foreach ($this->variables as $variable) {
                if (! $this->isDateVariable($variable)) continue;
                $value = $this->variableValues[$variable] ?? null;
                if (blank($value)) { $this->addError('variableValues.' . $variable, 'Tanggal wajib diisi.'); continue; }
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) { $this->addError('variableValues.' . $variable, 'Format tanggal tidak valid.'); continue; }
                if ($this->isBirthDateVariable($variable)) { if ($value > now()->toDateString()) $this->addError('variableValues.' . $variable, 'Tanggal lahir tidak boleh tanggal di masa depan.'); }
                elseif ($value > now()->toDateString()) $this->addError('variableValues.' . $variable, 'Tanggal tidak boleh melewati hari ini.');
            }
            if ($this->getErrorBag()->isNotEmpty()) return;
            $data = $this->variableValues;
            $data['number'] = (string) ($data['number'] ?? '');
            $data['recipient_name'] = (string) ($data['recipient_name'] ?? '');
            $data['recipient_address'] = (string) ($data['recipient_address'] ?? '');
            $data['subject'] = (string) ($data['subject'] ?? '');
            $letterTypeVersion = app(LetterTypeService::class)->activeVersion($letterType);
            $templateRelativePath = $letterTypeVersion?->template_path ?: $letterType->template_path;
            if (! $templateRelativePath) throw new \DomainException('Template DOCX surat belum tersedia.');
            $templatePath = Storage::disk('local')->path($templateRelativePath);
            if (! is_file($templatePath)) throw new \DomainException('File template DOCX tidak ditemukan di storage.');
            $existing = $this->editingId ? $this->tenantQuery()->findOrFail($this->editingId) : null;
            if ($existing) $this->authorize('update', $existing);
            $verificationToken = $existing?->verification_token ?? Str::random(64);
            $generatedPath = $docx->renderToStorage($templatePath, auth()->user()->tenant, $data);
            $tte->embed(Storage::disk('local')->path($generatedPath), url('/verify/' . $verificationToken));
            $content = $docx->extractText(Storage::disk('local')->path($generatedPath));
            $attributes = ['tenant_id' => $tenantId, 'letter_type_id' => $letterType->id, 'letter_type_version_id' => $letterTypeVersion?->id, 'signer_position_id' => $position->id, 'signer_user_id' => $holder->user_id, 'signer_name' => $holder->user->name, 'signer_title' => $position->name, 'validator_position_id' => $validatorPosition->id, 'validator_user_id' => $validatorHolder->user_id, 'validator_name' => $validatorHolder->user->name, 'validator_title' => $validatorPosition->name, 'number' => $data['number'], 'recipient_name' => $data['recipient_name'], 'recipient_address' => $data['recipient_address'], 'subject' => $data['subject'], 'letter_date' => $data['date'] ?? null, 'generated_docx_path' => $generatedPath, 'verification_token' => $verificationToken, 'content' => $content, 'input_data' => $data];
            if ($existing) { $oldPath = $existing->generated_docx_path; $service->update($existing, $attributes); if ($oldPath && $oldPath !== $generatedPath) Storage::disk('local')->delete($oldPath); $message = 'Draft surat berhasil diperbarui.'; }
            else { $attributes['created_by'] = auth()->id(); $attributes['status'] = OutgoingLetterStatus::DRAFT; $service->create($attributes, auth()->id()); $message = 'Draft surat berhasil dibuat.'; }
            $this->showForm = false;
            $this->resetForm();
            $this->dispatch('toast', type: 'success', message: $message);
        } catch (ValidationException $exception) { $this->setErrorBag($exception->validator->errors()); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal disimpan. Silakan coba lagi.'); }
    }

    public function submitLetter(string $id, OutgoingLetterService $service): void
    {
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('submit', $letter); $service->submit($letter, auth()->id()); $this->dispatch('toast', type: 'success', message: 'Surat dikirim untuk verifikasi. Data sekarang terkunci.'); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal dikirim untuk verifikasi.'); }
    }

    public function openReject(string $id): void
    {
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('reject', $letter); $this->rejectId = $id; $this->rejectReason = ''; $this->resetValidation(); $this->showRejectForm = true; }
        catch (\Throwable) { $this->toastError('Anda tidak memiliki kewenangan untuk menolak surat ini.'); }
    }

    public function rejectLetter(OutgoingLetterService $service): void
    {
        try { $this->validate(['rejectReason' => ['required', 'string', 'max:2000']]); $letter = $this->tenantQuery()->findOrFail($this->rejectId); $this->authorize('reject', $letter); $service->reject($letter, auth()->id(), $this->rejectReason); $this->showRejectForm = false; $this->rejectId = ''; $this->rejectReason = ''; $this->dispatch('toast', type: 'success', message: 'Surat ditolak dan dikembalikan kepada pembuat beserta alasan penolakan.'); }
        catch (ValidationException $exception) { $this->setErrorBag($exception->validator->errors()); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal ditolak.'); }
    }

    public function validateLetter(string $id, OutgoingLetterService $service, ?string $note = null): void
    {
        if (blank($note)) { $this->dispatch('workflow-note-required', action: 'validate', id: $id, title: 'Catatan Verifikasi', description: 'Berikan catatan pemeriksaan sebelum mengesahkan bahwa surat ini sudah diverifikasi.'); return; }
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('validate', $letter); $service->validate($letter, auth()->id(), $note); $this->dispatch('toast', type: 'success', message: 'Surat berhasil diverifikasi.'); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal diverifikasi. Anda mungkin tidak memiliki kewenangan atau status surat sudah berubah.'); }
    }

    public function issue(string $id, OutgoingLetterService $service, ?string $note = null, ?string $pin = null): void
    {
        if (! app(\App\Services\SignerPinService::class)->hasPin(auth()->user())) { $this->dispatch('signing-pin-missing', url: route('settings.signing-pin')); return; }
        if (blank($note)) { $this->dispatch('workflow-note-required', action: 'issue', id: $id, title: 'Catatan Penandatanganan', description: 'Berikan catatan sebelum menandatangani dan menerbitkan surat ini.'); return; }
        if (blank($pin)) { $this->dispatch('signer-pin-required', action: 'issue', id: $id, note: $note, title: 'PIN Tanda Tangan', description: 'Masukkan PIN tanda tangan Anda untuk melanjutkan proses penandatanganan.'); return; }
        try { $letter = $this->tenantQuery()->findOrFail($id); $this->authorize('issue', $letter); $service->issue($letter, auth()->id(), $note, $pin); $this->dispatch('toast', type: 'success', message: 'Surat berhasil diterbitkan.'); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal diterbitkan. Anda mungkin tidak memiliki kewenangan atau status surat sudah berubah.'); }
    }

    #[On('workflow-note-submitted')]
    public function handleWorkflowNote(string $action, string $id, string $note, OutgoingLetterService $service): void
    {
        $note = trim($note);
        if ($note === '') { $this->dispatch('workflow-note-required', action: $action, id: $id, title: $action === 'validate' ? 'Catatan Verifikasi' : 'Catatan Penandatanganan', description: 'Catatan wajib diisi.'); return; }
        if ($action === 'validate') $this->validateLetter($id, $service, $note);
        elseif ($action === 'issue') $this->dispatch('signer-pin-required', action: 'issue', id: $id, note: $note, title: 'PIN Tanda Tangan', description: 'Masukkan PIN tanda tangan Anda untuk melanjutkan proses penandatanganan.');
    }

    #[On('signer-pin-submitted')]
    public function handleSignerPin(string $action, string $id, string $note, string $pin, OutgoingLetterService $service): void
    {
        if ($action !== 'issue') return;
        $pin = trim($pin);
        if (! preg_match('/^\d{6}$/', $pin)) { $this->dispatch('signer-pin-invalid'); return; }
        $this->issue($id, $service, $note, $pin);
    }

    public function restoreLetter(string $id, OutgoingLetterService $service): void
    {
        try { $letter = OutgoingLetter::withTrashed()->findOrFail($id); $this->authorize('restore', $letter); $service->restore($letter); $this->dispatch('toast', type: 'success', message: 'Surat berhasil direstore.'); $this->resetPage(); }
        catch (\Throwable $exception) { $this->toastError($exception instanceof \DomainException ? $exception->getMessage() : 'Surat gagal direstore.'); }
    }

    private function applySystemValues(?PositionHolder $holder = null): void
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) return;
        $values = ['tenant_name' => $tenant->name, 'tenant_city' => $tenant->city, 'tenant_district' => $tenant->district, 'tenant_village' => $tenant->village, 'tenant_province' => $tenant->province, 'tenant_address' => $tenant->address, 'tenant_phone' => $tenant->phone, 'tenant_email' => $tenant->email, 'tenant_head_name' => $holder?->user?->name ?? $tenant->head_name, 'tenant_head_title' => $holder?->position?->name ?? $tenant->head_title];
        foreach ($this->variables as $variable) if ($this->isSystemVariable($variable)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
    }
    private function availableSignerPositions() { return $this->availablePositions('can_sign'); }
    private function availableValidatorPositions() { return $this->availablePositions('can_validate'); }
    private function availablePositions(string $capability) { $tenantId = auth()->user()->tenant_id; return Position::query()->where('tenant_id', $tenantId)->where('status', PositionStatus::ACTIVE)->where($capability, true)->whereHas('holders', fn($query) => $query->whereNull('ended_at')->where('started_at', '<=', now()))->with(['holders' => fn($query) => $query->whereNull('ended_at')->where('started_at', '<=', now())->with('user')]); }
    private function isSystemVariable(string $variable): bool { return in_array($variable, self::SYSTEM_VARIABLES, true); }
    private function isDateVariable(string $variable): bool { return (bool) preg_match('/(^|_)date$/i', $variable); }
    private function isBirthDateVariable(string $variable): bool { return (bool) preg_match('/(^|_)birth_date$/i', $variable); }
    private function isSuperAdmin(): bool { return auth()->user()->isSuperAdmin(); }
    private function tenantQuery() { return $this->isSuperAdmin() ? OutgoingLetter::query()->with(['letterType']) : OutgoingLetter::query()->where('tenant_id', auth()->user()->tenant_id)->with(['letterType']); }
    private function archiveQuery() { return $this->isSuperAdmin() ? OutgoingLetter::withTrashed() : $this->tenantQuery(); }
    private function resetForm(): void { $this->reset(['editingId', 'letter_type_id', 'signer_position_id', 'validator_position_id', 'variables', 'variableValues']); }
    private function toastError(string $message): void { $this->dispatch('toast', type: 'error', message: $message); }

    public function render()
    {
        $this->authorize('viewAny', OutgoingLetter::class);
        $letters = $this->archiveQuery()->with(['tenant', 'letterType', 'letterTypeVersion', 'signerPosition', 'signerUser', 'validatorPosition', 'validatorUser', 'creator', 'rejectedBy'])->latest();
        if ($this->isSuperAdmin() && $this->filter === 'deleted') $letters->onlyTrashed();
        elseif ($this->filter !== 'all') $letters->where('status', $this->filter);
        if ($this->search !== '') $letters->where(fn($q) => $q->where('number', 'like', "%{$this->search}%")->orWhere('recipient_name', 'like', "%{$this->search}%")->orWhere('subject', 'like', "%{$this->search}%"));
        $tenantId = auth()->user()->tenant_id;
        $letterTypeService = app(LetterTypeService::class);
        $letterTypes = $this->isSuperAdmin() || $tenantId === null ? collect() : $letterTypeService->getAvailableForTenant($tenantId)->sortBy('name')->values();
        return view('livewire.pages.outgoing-letters.index', ['letters' => $letters->paginate($this->perPage), 'letterTypes' => $letterTypes, 'signerPositions' => $this->availableSignerPositions()->orderBy('name')->get(), 'validatorPositions' => $this->availableValidatorPositions()->orderBy('name')->get(), 'variableLabels' => (new DocxTemplateService)->allowedVariables(), 'isSuperAdmin' => $this->isSuperAdmin()]);
    }
}
