<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Enums\LetterTypeStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Services\LetterTypeService;
use App\Services\OutgoingLetterService;
use App\Services\OutgoingLetterTemplateService;
use Illuminate\Validation\Rule;
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
    public array $variables = [];
    public array $variableValues = [];

    /** Variables resolved automatically from the logged-in tenant/system. */
    private const SYSTEM_VARIABLES = [
        'tenant_name',
        'tenant_city',
        'tenant_district',
        'tenant_village',
        'tenant_province',
        'tenant_address',
        'tenant_phone',
        'tenant_email',
        'tenant_head_name',
        'tenant_head_title',
        'date',
    ];

    public function create(): void
    {
        $this->authorize('create', OutgoingLetter::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function updatedLetterTypeId(): void
    {
        $type = LetterType::query()
            ->whereNull('tenant_id')
            ->where('status', LetterTypeStatus::ACTIVE)
            ->find($this->letter_type_id);

        $this->variables = $type?->variables ?? [];
        $this->variableValues = array_fill_keys($this->variables, '');
        $this->applySystemValues();
    }

    public function save(
        OutgoingLetterService $service,
        LetterTypeService $letterTypeService,
        OutgoingLetterTemplateService $templateService,
    ): void {
        $data = $this->validate([
            'letter_type_id' => ['required', Rule::exists('letter_types', 'id')->whereNull('tenant_id')->where('status', LetterTypeStatus::ACTIVE->value)],
            'variableValues' => ['array'],
        ]);

        $letterType = LetterType::query()
            ->whereNull('tenant_id')
            ->where('status', LetterTypeStatus::ACTIVE)
            ->findOrFail($this->letter_type_id);

        $this->authorize('view', $letterType);
        $this->applySystemValues();

        foreach ($this->variables as $variable) {
            if ($this->isSystemVariable($variable)) {
                continue;
            }

            if (blank($this->variableValues[$variable] ?? null)) {
                $this->addError('variableValues.' . $variable, 'Field ini wajib diisi.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $data = array_merge($data, $this->variableValues);

        // Keep the existing outgoing-letter columns populated from dynamic variables.
        $data['number'] = (string) ($data['number'] ?? '');
        $data['recipient_name'] = (string) ($data['recipient_name'] ?? '');
        $data['recipient_address'] = (string) ($data['recipient_address'] ?? '');
        $data['subject'] = (string) ($data['subject'] ?? '');

        $version = $letterTypeService->ensureCurrentVersion($letterType);
        $content = $version
            ? $templateService->renderVersion($version, auth()->user()->tenant, $data)
            : '';

        if ($content === '') {
            $this->addError('letter_type_id', 'Template surat belum tersedia.');
            return;
        }

        $service->create([
            ...$data,
            'tenant_id' => auth()->user()->tenant_id,
            'letter_type_version_id' => $version?->id,
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

    private function applySystemValues(): void
    {
        $tenant = auth()->user()->tenant;

        if (! $tenant) {
            return;
        }

        $systemValues = [
            'tenant_name' => $tenant->name,
            'tenant_city' => $tenant->city,
            'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village,
            'tenant_province' => $tenant->province,
            'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone,
            'tenant_email' => $tenant->email,
            'tenant_head_name' => $tenant->head_name,
            'tenant_head_title' => $tenant->head_title,
            'date' => now()->format('d-m-Y'),
        ];

        foreach ($this->variables as $variable) {
            if ($this->isSystemVariable($variable)) {
                $this->variableValues[$variable] = (string) ($systemValues[$variable] ?? '');
            }
        }
    }

    private function isSystemVariable(string $variable): bool
    {
        return in_array($variable, self::SYSTEM_VARIABLES, true);
    }

    private function tenantQuery()
    {
        return OutgoingLetter::query()->where('tenant_id', auth()->user()->tenant_id);
    }

    private function resetForm(): void
    {
        $this->reset(['letter_type_id', 'variables', 'variableValues']);
    }

    public function render()
    {
        $letters = $this->tenantQuery()->with(['letterType', 'letterTypeVersion'])->latest();

        if ($this->search !== '') {
            $letters->where(fn ($q) => $q
                ->where('number', 'like', "%{$this->search}%")
                ->orWhere('recipient_name', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%"));
        }

        if ($this->filter !== 'all') {
            $letters->where('status', $this->filter);
        }

        return view('livewire.pages.outgoing-letters.index', [
            'letters' => $letters->paginate($this->perPage),
            'letterTypes' => LetterType::query()
                ->whereNull('tenant_id')
                ->where('status', LetterTypeStatus::ACTIVE)
                ->orderBy('name')
                ->get(),
            'variableLabels' => (new OutgoingLetterTemplateService)->variables(),
        ]);
    }
}
