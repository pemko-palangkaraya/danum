<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetterWithdrawals;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Livewire\Concerns\WithStandardTablePagination;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Services\OutgoingLetterService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;
    use WithStandardTablePagination;

    public string $search = '';
    public string $filter = 'active';
    public int $perPage = 5;
    public int $pendingPerPage = 5;

    public function updatedSearch(): void
    {
        $this->resetPage('issuedPage');
        $this->resetPage('pendingPage');
    }

    public function updatedPerPage(): void
    {
        $this->perPage = max(5, min($this->perPage, 50));
        $this->resetPage('issuedPage');
    }

    public function updatedPendingPerPage(): void
    {
        $this->pendingPerPage = max(5, min($this->pendingPerPage, 50));
        $this->resetPage('pendingPage');
    }

    public ?string $selectedLetterId = null;
    public string $reason = '';
    public $statementFile = null;
    public ?string $decisionId = null;
    public string $decisionNote = '';
    public bool $showRequestForm = false;
    public bool $showDecisionForm = false;

    public function mount(?string $letter = null): void
    {
        $this->selectedLetterId = $letter;
        $this->authorizePage();
    }

    public function openRequest(string $id): void
    {
        $letter = $this->tenantIssuedLetters()->findOrFail($id);
        $this->authorize('requestWithdrawal', $letter);
        $this->selectedLetterId = $id;
        $this->reason = '';
        $this->statementFile = null;
        $this->resetValidation();
        $this->showRequestForm = true;
    }

    public function submitRequest(OutgoingLetterService $service): void
    {
        $this->authorizePage();
        $this->validate([
            'selectedLetterId' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:2000'],
            'statementFile' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $letter = $this->tenantIssuedLetters()->findOrFail($this->selectedLetterId);
        $this->authorize('requestWithdrawal', $letter);

        $path = null;
        try {
            $path = $this->statementFile->store('withdrawal-statements', 'local');
            $service->requestWithdrawal($letter, auth()->id(), $this->reason, $path);
            $this->showRequestForm = false;
            $this->reset(['reason', 'statementFile']);
            $this->dispatch('toast', type: 'success', message: 'Pengajuan penarikan berhasil dikirim untuk persetujuan Super Admin.');
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            $this->addError('reason', $exception instanceof \DomainException ? $exception->getMessage() : 'Pengajuan penarikan gagal diproses.');
        }
    }

    public function openDecision(string $id): void
    {
        $request = $this->pendingRequests()->findOrFail($id);
        $this->authorize('decideWithdrawal', $request->outgoingLetter);
        $this->decisionId = $id;
        $this->decisionNote = '';
        $this->resetValidation();
        $this->showDecisionForm = true;
    }

    public function approve(OutgoingLetterService $service): void
    {
        $this->authorizePage();
        $this->validate(['decisionNote' => ['required', 'string', 'max:2000']]);
        $request = $this->pendingRequests()->findOrFail($this->decisionId);
        $this->authorize('decideWithdrawal', $request->outgoingLetter);
        try {
            $service->approveWithdrawal($request, auth()->id(), $this->decisionNote);
            $this->showDecisionForm = false;
            $this->dispatch('toast', type: 'success', message: 'Penarikan disetujui. Surat sekarang berstatus Ditarik.');
        } catch (\Throwable $exception) {
            $this->addError('decisionNote', $exception instanceof \DomainException ? $exception->getMessage() : 'Persetujuan gagal diproses.');
        }
    }

    public function reject(OutgoingLetterService $service): void
    {
        $this->authorizePage();
        $this->validate(['decisionNote' => ['required', 'string', 'max:2000']]);
        $request = $this->pendingRequests()->findOrFail($this->decisionId);
        $this->authorize('decideWithdrawal', $request->outgoingLetter);
        try {
            $service->rejectWithdrawal($request, auth()->id(), $this->decisionNote);
            $this->showDecisionForm = false;
            $this->dispatch('toast', type: 'success', message: 'Pengajuan penarikan ditolak. Surat tetap berstatus Issued.');
        } catch (\Throwable $exception) {
            $this->addError('decisionNote', $exception instanceof \DomainException ? $exception->getMessage() : 'Penolakan gagal diproses.');
        }
    }

    private function authorizePage(): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->tenant_id !== null, 403);
    }

    private function tenantIssuedLetters()
    {
        $query = OutgoingLetter::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('created_by', auth()->id())
            ->where('status', OutgoingLetterStatus::ISSUED)
            ->with(['tenant', 'letterType', 'withdrawalRequests']);

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'ilike', '%' . $search . '%')
                    ->orWhere('subject', 'ilike', '%' . $search . '%')
                    ->orWhere('recipient_name', 'ilike', '%' . $search . '%')
                    ->orWhereHas('letterType', fn ($type) => $type->where('name', 'ilike', '%' . $search . '%'));
            });
        }

        return $query;
    }

    private function pendingRequests()
    {
        $query = OutgoingLetterWithdrawalRequest::query()
            ->where('status', OutgoingLetterWithdrawalStatus::PENDING)
            ->with(['outgoingLetter.tenant', 'outgoingLetter.letterType', 'requestedBy']);

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('outgoingLetter', function ($letter) use ($search) {
                    $letter->where('number', 'ilike', '%' . $search . '%')
                        ->orWhere('subject', 'ilike', '%' . $search . '%')
                        ->orWhere('recipient_name', 'ilike', '%' . $search . '%')
                        ->orWhereHas('tenant', fn ($tenant) => $tenant->where('name', 'ilike', '%' . $search . '%'))
                        ->orWhereHas('letterType', fn ($type) => $type->where('name', 'ilike', '%' . $search . '%'));
                })
                    ->orWhereHas('requestedBy', fn ($user) => $user->where('name', 'ilike', '%' . $search . '%'));
            });
        }

        return $query;
    }

    public function render()
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('livewire.outgoing-letter-withdrawals.index', [
            'isSuperAdmin' => $isSuperAdmin,
            'issuedLetters' => $isSuperAdmin ? collect() : $this->tenantIssuedLetters()->latest('issued_at')->paginate($this->perPage, ['*'], 'issuedPage'),
            'pendingRequests' => $isSuperAdmin ? $this->pendingRequests()->latest('requested_at')->paginate($this->pendingPerPage, ['*'], 'pendingPage') : collect(),
            'selectedLetter' => $this->selectedLetterId && ! $isSuperAdmin ? $this->tenantIssuedLetters()->find($this->selectedLetterId) : null,
        ]);
    }
}
