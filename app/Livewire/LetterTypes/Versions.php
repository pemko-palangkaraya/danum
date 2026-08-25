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

    // Keep only the UUID in Livewire state. Eloquent models are serialized by
    // Livewire between requests and may come back as an attribute array,
    // which must never be passed to a UUID query as the primary key.
    public string $letterTypeId = '';
    public bool $showForm = false;
    public $template_file = null;
    public string $effective_from = '';
    public string $effective_until = '';
    public string $change_note = '';

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
        $this->effective_from = now()->format('Y-m-d\TH:i');
        $this->showForm = true;
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

        $storedPath = null;
        try {
            $declared = $letterType->variables ?? [];
            $temporaryPath = $this->template_file->getRealPath();
            $found = $docx->extractVariables($temporaryPath);
            $diff = $docx->compareVariables($declared, $found);

            if ($diff['unknown']) {
                $this->addError('template_file', 'Template baru memiliki variabel yang belum terdaftar: {{'.implode('}}, {{', $diff['unknown']).'}}.');
                return;
            }
            if ($diff['missing']) {
                $this->addError('template_file', 'Template baru tidak memuat variabel yang masih diwajibkan: {{'.implode('}}, {{', $diff['missing']).'}}.');
                return;
            }

            $storedPath = $this->template_file->store('letter-templates', 'local');

            $service->createVersion($letterType, [
                'template_path' => $storedPath,
                'body_template' => $letterType->body_template,
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
        $this->dispatch('toast', type: 'success', message: 'Versi template berhasil dibuat. Versi lama tetap dipertahankan.');
    }

    private function letterType(): LetterType
    {
        return LetterType::query()->findOrFail($this->letterTypeId);
    }

    private function resetForm(): void
    {
        $this->template_file = null;
        $this->effective_from = '';
        $this->effective_until = '';
        $this->change_note = '';
    }

    public function render()
    {
        $letterType = $this->letterType();

        return view('livewire.pages.letter-types.versions', [
            'letterType' => $letterType,
            'versions' => $letterType->versions()->with('creator')->get(),
        ]);
    }
}
