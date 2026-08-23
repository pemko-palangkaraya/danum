<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Enums\PositionStatus;
use App\Enums\UserRole;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use App\Services\DocxTemplateService;
use App\Services\DocxTteService;
use App\Services\OutgoingLetterService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filter = 'all';
    public int $perPage = 5;
    public bool $showForm = false;
    public string $letter_type_id = '';
    public string $signer_position_id = '';
    public array $variables = [];
    public array $variableValues = [];

    private const SYSTEM_VARIABLES = ['letterhead','tenant_name','tenant_city','tenant_district','tenant_village','tenant_province','tenant_address','tenant_phone','tenant_email','tenant_head_name','tenant_head_title','tte'];

    public function create(): void
    {
        $this->authorize('create', OutgoingLetter::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function updatedLetterTypeId(): void
    {
        $type = LetterType::query()->whereNull('tenant_id')->where('status', LetterTypeStatus::ACTIVE)->find($this->letter_type_id);
        $this->variables = $type?->variables ?? [];
        $this->variableValues = array_fill_keys($this->variables, '');
        $this->applySystemValues();
    }

    public function save(OutgoingLetterService $service, DocxTemplateService $docx, DocxTteService $tte): void
    {
        $this->validate([
            'letter_type_id' => ['required', Rule::exists('letter_types', 'id')->whereNull('tenant_id')->where('status', LetterTypeStatus::ACTIVE->value)],
            'signer_position_id' => ['required', 'uuid'],
            'variableValues' => ['array'],
        ]);

        $letterType = LetterType::query()->whereNull('tenant_id')->where('status', LetterTypeStatus::ACTIVE)->findOrFail($this->letter_type_id);
        $this->authorize('view', $letterType);

        $position = $this->availableSignerPositions()->find($this->signer_position_id);
        if (! $position) {
            $this->addError('signer_position_id', 'Jabatan penanda tangan tidak tersedia atau tidak memiliki pejabat aktif.');
            return;
        }

        $holder = $position->holders->first();
        if (! $holder?->user) {
            $this->addError('signer_position_id', 'Jabatan tersebut belum memiliki pejabat aktif.');
            return;
        }

        $this->applySystemValues($holder);

        foreach ($this->variables as $variable) {
            if ($this->isSystemVariable($variable)) continue;
            if (blank($this->variableValues[$variable] ?? null)) $this->addError('variableValues.'.$variable, 'Field ini wajib diisi.');
        }

        foreach ($this->variables as $variable) {
            if (! $this->isDateVariable($variable)) continue;
            $value = $this->variableValues[$variable] ?? null;
            if (blank($value)) { $this->addError('variableValues.'.$variable, 'Tanggal wajib diisi.'); continue; }
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) { $this->addError('variableValues.'.$variable, 'Format tanggal tidak valid.'); continue; }
            if ($value === now()->toDateString()) $this->addError('variableValues.'.$variable, 'Tanggal tidak boleh tanggal hari ini.');
            if ($this->isBirthDateVariable($variable) && $value > now()->toDateString()) $this->addError('variableValues.'.$variable, 'Tanggal lahir tidak boleh tanggal di masa depan.');
        }
        if ($this->getErrorBag()->isNotEmpty()) return;

        $data = $this->variableValues;
        $data['number'] = (string) ($data['number'] ?? '');
        $data['recipient_name'] = (string) ($data['recipient_name'] ?? '');
        $data['recipient_address'] = (string) ($data['recipient_address'] ?? '');
        $data['subject'] = (string) ($data['subject'] ?? '');

        if (! $letterType->template_path) { $this->addError('letter_type_id', 'Template DOCX surat belum tersedia.'); return; }
        $templatePath = Storage::disk('local')->path($letterType->template_path);
        if (! is_file($templatePath)) { $this->addError('letter_type_id', 'File template DOCX tidak ditemukan di storage.'); return; }

        $verificationToken = Str::random(64);
        $verificationUrl = url('/verify/' . $verificationToken);
        $generatedPath = $docx->renderToStorage($templatePath, auth()->user()->tenant, $data);
        $tte->embed(Storage::disk('local')->path($generatedPath), $verificationUrl);
        $content = $docx->extractText(Storage::disk('local')->path($generatedPath));

        $service->create([
            'tenant_id' => auth()->user()->tenant_id,
            'letter_type_id' => $letterType->id,
            'letter_type_version_id' => null,
            'signer_position_id' => $position->id,
            'signer_user_id' => $holder->user_id,
            'signer_name' => $holder->user->name,
            'signer_title' => $position->name,
            'number' => $data['number'],
            'recipient_name' => $data['recipient_name'],
            'recipient_address' => $data['recipient_address'],
            'subject' => $data['subject'],
            'letter_date' => $data['date'] ?? null,
            'generated_docx_path' => $generatedPath,
            'verification_token' => $verificationToken,
            'content' => $content,
            'status' => OutgoingLetterStatus::DRAFT,
        ], auth()->id());

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Draft surat berhasil dibuat.');
    }

    public function validateLetter(string $id, OutgoingLetterService $service): void
    {
        $letter = $this->tenantQuery()->findOrFail($id);
        $this->authorize('validate', $letter);
        $service->validate($letter, auth()->id());
        $this->dispatch('toast', type: 'success', message: 'Surat berhasil divalidasi.');
    }

    public function issue(string $id, OutgoingLetterService $service): void
    {
        $letter = $this->tenantQuery()->findOrFail($id);
        $this->authorize('issue', $letter);
        $service->issue($letter, auth()->id());
        $this->dispatch('toast', type: 'success', message: 'Surat berhasil diterbitkan.');
    }

    public function restoreLetter(string $id, OutgoingLetterService $service): void
    {
        $letter = OutgoingLetter::withTrashed()->findOrFail($id);
        $this->authorize('restore', $letter);
        try {
            $service->restore($letter);
        } catch (\DomainException $exception) {
            $this->addError('restore', $exception->getMessage());
            return;
        }

        $this->dispatch('toast', type: 'success', message: 'Surat berhasil direstore.');
        $this->resetPage();
    }

    private function applySystemValues(?PositionHolder $holder = null): void
    {
        $tenant = auth()->user()->tenant;
        if (! $tenant) return;

        $values = [
            'tenant_name' => $tenant->name,
            'tenant_city' => $tenant->city,
            'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village,
            'tenant_province' => $tenant->province,
            'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone,
            'tenant_email' => $tenant->email,
            'tenant_head_name' => $holder?->user?->name ?? $tenant->head_name,
            'tenant_head_title' => $holder?->position?->name ?? $tenant->head_title,
        ];

        foreach ($this->variables as $variable) {
            if ($this->isSystemVariable($variable)) $this->variableValues[$variable] = (string) ($values[$variable] ?? '');
        }
    }

    private function availableSignerPositions()
    {
        return Position::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('status', PositionStatus::ACTIVE)
            ->where('can_sign', true)
            ->whereHas('holders', fn ($query) => $query
                ->whereNull('ended_at')
                ->where('started_at', '<=', now()))
            ->with(['holders' => fn ($query) => $query
                ->whereNull('ended_at')
                ->where('started_at', '<=', now())
                ->with('user')]);
    }

    private function isSystemVariable(string $variable): bool { return in_array($variable, self::SYSTEM_VARIABLES, true); }
    private function isDateVariable(string $variable): bool { return (bool) preg_match('/(^|_)date$/i', $variable); }
    private function isBirthDateVariable(string $variable): bool { return (bool) preg_match('/(^|_)birth_date$/i', $variable); }
    private function isSuperAdmin(): bool { return auth()->user()->role === UserRole::SUPER_ADMIN; }
    private function tenantQuery() { return OutgoingLetter::query()->where('tenant_id', auth()->user()->tenant_id); }
    private function archiveQuery()
    {
        return $this->isSuperAdmin()
            ? OutgoingLetter::withTrashed()
            : $this->tenantQuery();
    }
    private function resetForm(): void { $this->reset(['letter_type_id','signer_position_id','variables','variableValues']); }

    public function render()
    {
        $this->authorize('viewAny', OutgoingLetter::class);

        $letters = $this->archiveQuery()
            ->with(['tenant','letterType','letterTypeVersion','signerPosition','signerUser'])
            ->latest();

        if ($this->isSuperAdmin() && $this->filter === 'deleted') {
            $letters->onlyTrashed();
        } elseif ($this->filter !== 'all') {
            $letters->where('status', $this->filter);
        }

        if ($this->search !== '') {
            $letters->where(fn ($q) => $q
                ->where('number','like',"%{$this->search}%")
                ->orWhere('recipient_name','like',"%{$this->search}%")
                ->orWhere('subject','like',"%{$this->search}%"));
        }

        return view('livewire.pages.outgoing-letters.index', [
            'letters' => $letters->paginate($this->perPage),
            'letterTypes' => LetterType::query()->whereNull('tenant_id')->where('status', LetterTypeStatus::ACTIVE)->orderBy('name')->get(),
            'signerPositions' => $this->availableSignerPositions()->orderBy('name')->get(),
            'variableLabels' => (new DocxTemplateService)->allowedVariables(),
            'isSuperAdmin' => $this->isSuperAdmin(),
        ]);
    }
}
