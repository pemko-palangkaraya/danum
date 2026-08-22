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
    public string $number = '';
    public string $recipient_name = '';
    public string $recipient_address = '';
    public string $subject = '';

    public function create(): void
    {
        $this->authorize('create', OutgoingLetter::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(
        OutgoingLetterService $service,
        LetterTypeService $letterTypeService,
        OutgoingLetterTemplateService $templateService,
    ): void {
        $data = $this->validate([
            'letter_type_id' => [
                'required',
                Rule::exists('letter_types', 'id')->where('tenant_id', auth()->user()->tenant_id)->where('status', LetterTypeStatus::ACTIVE->value),
            ],
            'number' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_address' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
        ]);

        $letterType = LetterType::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($this->letter_type_id);
        $this->authorize('view', $letterType);

        $version = $letterTypeService->ensureCurrentVersion($letterType);
        $content = $version
            ? $templateService->renderVersion($version, auth()->user()->tenant, $data)
            : '';

        if ($content === '') {
            $this->addError('subject', 'Template surat belum tersedia.');
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

    private function tenantQuery()
    {
        return OutgoingLetter::query()->where('tenant_id', auth()->user()->tenant_id);
    }

    private function resetForm(): void
    {
        $this->reset(['letter_type_id', 'number', 'recipient_name', 'recipient_address', 'subject']);
    }

    public function render()
    {
        $letters = $this->tenantQuery()->with(['letterType', 'letterTypeVersion'])->latest();
        if ($this->search !== '') {
            $letters->where(fn ($q) => $q->where('number', 'like', "%{$this->search}%")
                ->orWhere('recipient_name', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%"));
        }
        if ($this->filter !== 'all') {
            $letters->where('status', $this->filter);
        }

        return view('livewire.pages.outgoing-letters.index', [
            'letters' => $letters->paginate($this->perPage),
            'letterTypes' => LetterType::query()->where('tenant_id', auth()->user()->tenant_id)->where('status', LetterTypeStatus::ACTIVE)->orderBy('name')->get(),
        ]);
    }
}
