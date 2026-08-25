<?php

declare(strict_types=1);

namespace App\Livewire\LetterTypes;

use App\Models\LetterType;
use App\Services\DocxTemplateService;
use App\Services\LetterTypeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Versions extends Component
{
    use WithFileUploads;

    public string $letterTypeId = '';
    public bool $showForm = false;
    public $template_file = null;
    public string $effective_from = '';
    public string $effective_until = '';
    public string $change_note = '';
    public string $templateCheckStatus = '';
    public array $versionVariables = [];
    public array $templateFoundVariables = [];
    public array $templateUnknownVariables = [];
    public array $templateMissingVariables = [];

    public function mount(string|LetterType $letterType): void
    {
        $this->authorize('viewAny', LetterType::class);
        $this->letterTypeId = $letterType instanceof LetterType ? $letterType->getKey() : $letterType;
        $model = $this->letterType();
        $this->authorize('view', $model);
        $this->resetForm();
    }

    public function create(): void
    {
        $letterType = $this->letterType();
        $this->authorize('update', $letterType);
        $this->resetForm();
        $this->versionVariables = $this->normalizedVariables($letterType->variables ?? []);
        $this->effective_from = now()->format('Y-m-d\TH:i');
        $this->showForm = true;
    }

    public function updatedTemplateFile(DocxTemplateService $docx): void { $this->validateTemplate($docx); }
    public function checkTemplate(DocxTemplateService $docx): void { $this->validateTemplate($docx); }

    public function addFoundVariables(DocxTemplateService $docx): void
    {
        if (! $this->templateUnknownVariables) return;
        $this->versionVariables = $this->normalizedVariables(array_merge($this->versionVariables, $this->templateUnknownVariables));
        $this->validateTemplate($docx);
    }

    private function validateTemplate(DocxTemplateService $docx): bool
    {
        $this->resetValidation('template_file');
        $this->templateCheckStatus = '';
        $this->templateFoundVariables = [];
        $this->templateUnknownVariables = [];
        $this->templateMissingVariables = [];
        if (! $this->template_file) return false;

        $validator = validator(['template_file' => $this->template_file], ['template_file' => ['required', 'file', 'mimes:docx', 'max:10240']]);
        if ($validator->fails()) {
            $this->addError('template_file', $validator->errors()->first('template_file'));
            return false;
        }

        try {
            $found = $docx->extractVariables($this->template_file->getRealPath());
            $declared = $this->normalizedVariables($this->versionVariables);
            $diff = $docx->compareVariables($declared, $found);
            $this->templateFoundVariables = array_values($found);
            $this->templateUnknownVariables = array_values($diff['unknown']);
            $this->templateMissingVariables = array_values($diff['missing']);
            $this->templateCheckStatus = ($diff['unknown'] || $diff['missing']) ? 'failed' : 'passed';
            return $this->templateCheckStatus === 'passed';
        } catch (\Throwable $e) {
            $this->addError('template_file', 'Template tidak dapat diperiksa: '.$e->getMessage());
            $this->templateCheckStatus = 'failed';
            return false;
        }
    }

    public function save(LetterTypeService $service, DocxTemplateService $docx): void
    {
        $letterType = $this->letterType();
        $this->authorize('update', $letterType);
        $this->validate([
            'template_file' => ['required', 'file', 'mimes:docx', 'max:10240'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after:effective_from'],
            'change_note' => ['required', 'string', 'max:2000'],
        ]);

        $this->versionVariables = $this->normalizedVariables($this->versionVariables);
        if (! $this->versionVariables) {
            $this->addError('template_file', 'Versi template wajib memiliki minimal satu variabel input.');
            return;
        }
        if (! $this->validateTemplate($docx)) return;

        $storedPath = null;
        try {
            $storedPath = $this->template_file->store('letter-templates', 'local');
            $service->createVersion($letterType, [
                'template_path' => $storedPath,
                'body_template' => $letterType->body_template,
                'variables' => $this->versionVariables,
                'effective_from' => Carbon::parse($this->effective_from),
                'effective_until' => $this->effective_until !== '' ? Carbon::parse($this->effective_until) : null,
                'change_note' => $this->change_note,
            ], (int) auth()->id());
        } catch (\Throwable $e) {
            if ($storedPath) Storage::disk('local')->delete($storedPath);
            throw $e;
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: 'Versi template berhasil dibuat. Variabel versi disimpan sebagai snapshot.');
    }

    private function normalizedVariables(array $variables): array
    {
        return array_values(array_unique(array_filter(array_map(static fn ($value) => trim((string) $value), $variables))));
    }

    private function letterType(): LetterType { return LetterType::query()->findOrFail($this->letterTypeId); }

    private function resetForm(): void
    {
        $this->template_file = null;
        $this->effective_from = '';
        $this->effective_until = '';
        $this->change_note = '';
        $this->templateCheckStatus = '';
        $this->versionVariables = [];
        $this->templateFoundVariables = [];
        $this->templateUnknownVariables = [];
        $this->templateMissingVariables = [];
    }

    public function render()
    {
        $letterType = $this->letterType();
        return view('livewire.pages.letter-types.versions', [
            'letterType' => $letterType,
            'declaredVariables' => $this->versionVariables ?: $this->normalizedVariables($letterType->variables ?? []),
            'versions' => $letterType->versions()->with('creator')->get(),
        ]);
    }
}
