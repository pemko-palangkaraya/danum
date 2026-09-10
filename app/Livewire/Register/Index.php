<?php

declare(strict_types=1);

namespace App\Livewire\Register;

use App\Enums\RegisterEntrySource;
use App\Livewire\Concerns\WithStandardTablePagination;
use App\Models\RegisterEntry;
use App\Services\RegisterEntryService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithStandardTablePagination;

    public string $search = '';
    public string $source = 'all';
    public int $year;
    public bool $showManualForm = false;
    public ?string $editingId = null;
    public string $letter_number = '';
    public string $letter_date = '';
    public string $letter_type_name = '';
    public string $classification_code = '';
    public string $subject = '';
    public string $recipient_name = '';
    public string $recipient_address = '';
    public string $signer_name = '';
    public string $signer_title = '';
    public string $correction_reason = '';

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->letter_date = now()->toDateString();
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedSource(): void { $this->resetPage(); }
    public function updatedYear(): void { $this->resetPage(); }

    public function openManualForm(): void
    {
        try {
            $this->authorizeManualAccess();
            $this->resetForm();
            $this->showManualForm = true;
        } catch (\Throwable $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    public function editManual(string $id): void
    {
        try {
            $this->authorizeManualAccess();
            $entry = $this->tenantQuery()->findOrFail($id);
            if ($entry->source !== RegisterEntrySource::MANUAL) throw new \DomainException('Register dari DANUM tidak diedit dari halaman ini.');
            $this->editingId = $entry->id;
            $this->letter_number = $entry->letter_number;
            $this->letter_date = $entry->letter_date->toDateString();
            $this->letter_type_name = $entry->letter_type_name ?? '';
            $this->classification_code = $entry->classification_code ?? '';
            $this->subject = $entry->subject;
            $this->recipient_name = $entry->recipient_name;
            $this->recipient_address = $entry->recipient_address ?? '';
            $this->signer_name = $entry->signer_name ?? '';
            $this->signer_title = $entry->signer_title ?? '';
            $this->correction_reason = '';
            $this->resetValidation();
            $this->showManualForm = true;
        } catch (\Throwable $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    public function saveManual(RegisterEntryService $service): void
    {
        try {
            $this->authorizeManualAccess();
            $rules = [
                'letter_number' => ['required', 'string', 'max:100'],
                'letter_date' => ['required', 'date'],
                'letter_type_name' => ['nullable', 'string', 'max:255'],
                'classification_code' => ['nullable', 'string', 'max:100'],
                'subject' => ['required', 'string', 'max:255'],
                'recipient_name' => ['required', 'string', 'max:255'],
                'recipient_address' => ['nullable', 'string'],
                'signer_name' => ['nullable', 'string', 'max:255'],
                'signer_title' => ['nullable', 'string', 'max:255'],
            ];
            if ($this->editingId) $rules['correction_reason'] = ['required', 'string', 'max:2000'];
            $this->validate($rules);

            $data = $this->only(['letter_number', 'letter_date', 'letter_type_name', 'classification_code', 'subject', 'recipient_name', 'recipient_address', 'signer_name', 'signer_title']);
            if ($this->editingId) {
                $data['correction_reason'] = $this->correction_reason;
                $entry = $this->tenantQuery()->findOrFail($this->editingId);
                $service->correct($entry, $data, auth()->user());
                $message = 'Register manual berhasil dikoreksi.';
            } else {
                $service->createManual($data, auth()->user());
                $message = 'Surat manual berhasil masuk buku register.';
            }

            $this->showManualForm = false;
            $this->resetForm();
            $this->toast($message);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        } catch (\Throwable $exception) {
            $this->toast($exception->getMessage(), 'error');
        }
    }

    private function authorizeManualAccess(): void
    {
        abort_unless(auth()->user()?->isTenantUser() && auth()->user()->hasPermission('outgoing-letters.create'), 403);
    }

    private function tenantQuery()
    {
        return RegisterEntry::query()->where('tenant_id', auth()->user()->tenant_id);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'letter_number', 'letter_type_name', 'classification_code', 'subject', 'recipient_name', 'recipient_address', 'signer_name', 'signer_title', 'correction_reason']);
        $this->letter_date = now()->toDateString();
        $this->resetValidation();
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', type: $type, message: $message);
    }

    public function render()
    {
        abort_unless(auth()->user()?->hasPermission('outgoing-letters.view'), 403);

        $query = $this->tenantQuery();
        $query->where('register_year', $this->year);
        if ($this->source !== 'all') $query->where('source', $this->source);
        if ($this->search !== '') {
            $search = "%{$this->search}%";
            $query->where(fn ($q) => $q->where('letter_number', 'like', $search)->orWhere('subject', 'like', $search)->orWhere('recipient_name', 'like', $search));
        }

        return view('livewire.pages.register.index', [
            'entries' => $query->orderByDesc('register_number')->paginate($this->perPage),
        ]);
    }
}
