<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterAttachmentService;
use App\Services\OutgoingLetterDocumentService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Attachments extends Component
{
    use WithFileUploads;

    public string $letterId = '';
    public array $attachmentFiles = [];
    public array $attachmentTitles = [];

    public function mount(string $letterId): void
    {
        $this->letterId = $letterId;
    }

    public function upload(OutgoingLetterAttachmentService $attachments, OutgoingLetterDocumentService $documents): void
    {
        $letter = $this->letter();
        $this->authorizeMutable($letter);

        $this->validate([
            'attachmentFiles' => ['required', 'array', 'max:20'],
            'attachmentFiles.*' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:20480'],
            'attachmentTitles' => ['array'],
        ]);

        $attachments->addUploads($letter, $this->attachmentFiles, $this->attachmentTitles);
        $documents->regenerate($letter->fresh());
        $this->reset(['attachmentFiles', 'attachmentTitles']);
        $this->dispatch('outgoing-letter-pdf-refresh');
        $this->dispatch('toast', type: 'success', message: 'Lampiran berhasil ditambahkan.');
    }

    public function remove(string $id, OutgoingLetterAttachmentService $attachments, OutgoingLetterDocumentService $documents): void
    {
        $letter = $this->letter();
        $this->authorizeMutable($letter);
        $attachment = $letter->attachments()->findOrFail($id);
        $attachments->delete($attachment);
        $documents->regenerate($letter->fresh());
        $this->dispatch('outgoing-letter-pdf-refresh');
        $this->dispatch('toast', type: 'success', message: 'Lampiran berhasil dihapus.');
    }

    public function move(string $id, int $direction, OutgoingLetterAttachmentService $attachments, OutgoingLetterDocumentService $documents): void
    {
        $letter = $this->letter();
        $this->authorizeMutable($letter);
        $attachment = $letter->attachments()->findOrFail($id);
        $attachments->move($attachment, $direction);
        $documents->regenerate($letter->fresh());
        $this->dispatch('outgoing-letter-pdf-refresh');
    }

    public function render()
    {
        $letter = $this->letter();
        return view('livewire.outgoing-letters.attachments', [
            'letter' => $letter,
            'attachments' => $letter->attachments()->orderBy('sequence')->get(),
            'totalPages' => app(OutgoingLetterAttachmentService::class)->totalPages($letter),
            'editable' => $this->isEditable($letter),
        ]);
    }

    private function letter(): OutgoingLetter
    {
        $tenantId = auth()->user()?->tenant_id;
        if ($tenantId === null) abort(403);
        return OutgoingLetter::query()->where('tenant_id', $tenantId)->findOrFail($this->letterId);
    }

    private function isEditable(OutgoingLetter $letter): bool
    {
        return $letter->status->value === 'draft' && $letter->submitted_at === null && (int) $letter->created_by === (int) auth()->id();
    }

    private function authorizeMutable(OutgoingLetter $letter): void
    {
        if (! $this->isEditable($letter)) abort(403, 'Lampiran hanya dapat diubah pada draft milik pembuat surat.');
        $this->authorize('update', $letter);
    }
}
