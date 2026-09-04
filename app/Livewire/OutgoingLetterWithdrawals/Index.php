<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetterWithdrawals;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Livewire\Concerns\WithStandardTablePagination;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Services\OutgoingLetterWithdrawalService;
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
    public int $perPage = 5;
    public int $pendingPerPage = 5;
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

    public function updatedSearch(): void
    {
        $this->resetPage('issuedPage');
        $this->resetPage('pendingPage');
    }

    public function updatedPerPage(): void
    {
        $this->perPage = $this->normalizePerPage($this->perPage);
        $this->resetPage('issuedPage');
    }

    public function updatedPendingPerPage(): void
    {
        $this->pendingPerPage = $this->normalizePerPage($this->pendingPerPage);
        $this->resetPage('pendingPage');
    }

    public function openRequest(string $id): void
    {
        $letter = $this->issuedLettersQuery()->findOrFail($id);
        $this->authorize('requestWithdrawal', $letter);

        $this->selectedLetterId = $id;
        $this->reason = '';
        $this->statementFile = null;
        $this->resetValidation();
        $this->showRequestForm = true;
    }

    public function submitRequest(OutgoingLetterWithdrawalService $service): void
    {
        $this->authorizePage();
        $this->validateRequest();

        $letter = $this->issuedLettersQuery()->findOrFail($this->selectedLetterId);
        $this->authorize('requestWithdrawal', $letter);

        $path = null;

        try {
            $path = $this->statementFile->store('withdrawal-statements', 'local');
            $service->request($letter, auth()->id(), $this->reason, $path);
            $this->showRequestForm = false;
            $this->reset(['reason', 'statementFile']);
            $this->success('Pengajuan penarikan berhasil dikirim untuk persetujuan Super Admin.');
        } catch (\Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            $this->addError('reason', $this->domainError($exception, 'Pengajuan penarikan gagal diproses.'));
        }
    }

    public function openDecision(string $id): void
    {
        $request = $this->pendingRequestsQuery()->findOrFail($id);
        $this->authorize('decideWithdrawal', $request->outgoingLetter);

        $this->decisionId = $id;
        $this->decisionNote = '';
        $this->resetValidation();
        $this->showDecisionForm = true;
    }

    public function approve(OutgoingLetterWithdrawalService $service): void
    {
        $this->decide($service, true);
    }

    public function reject(OutgoingLetterWithdrawalService $service): void
    {
        $this->decide($service, false);
    }

    private function decide(OutgoingLetterWithdrawalService $service, bool $approve): void
    {
        $this->authorizePage();
        $this->validate(['decisionNote' => ['required', 'string', 'max:2000']]);

        $request = $this->pendingRequestsQuery()->findOrFail($this->decisionId);
        $this->authorize('decideWithdrawal', $request->outgoingLetter);

        try {
            if ($approve) {
                $service->approve($request, auth()->id(), $this->decisionNote);
                $message = 'Penarikan disetujui. Surat sekarang berstatus Ditarik.';
            } else {
                $service->reject($request, auth()->id(), $this->decisionNote);
                $message = 'Pengajuan penarikan ditolak. Surat tetap berstatus Issued.';
            }

            $this->showDecisionForm = false;
            $this->success($message);
        } catch (\Throwable $exception) {
            $this->addError('decisionNote', $this->domainError($exception, $approve ? 'Persetujuan gagal diproses.' : 'Penolakan gagal diproses.'));
        }
    }

    private function authorizePage(): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->isSuperAdmin() || auth()->user()->tenant_id !== null, 403);
    }

    private function issuedLettersQuery()
    {
        $query = OutgoingLetter::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('created_by', auth()->id())
            ->where('status', OutgoingLetterStatus::ISSUED)
            ->with(['tenant', 'letterType', 'withdrawalRequests']);

        $this->applySearch($query, ['number', 'subject', 'recipient_name'], ['letterType']);

        return $query;
    }

    private function pendingRequestsQuery()
    {
        $query = OutgoingLetterWithdrawalRequest::query()
            ->where('status', OutgoingLetterWithdrawalStatus::PENDING)
            ->with(['outgoingLetter.tenant', 'outgoingLetter.letterType', 'requestedBy']);

        $search = trim($this->search);
        if ($search === '') {
            return $query;
        }

        return $query->where(function ($q) use ($search): void {
            $q->whereHas('outgoingLetter', function ($letter) use ($search): void {
                $letter->where(function ($nested) use ($search): void {
                    $nested->where('number', 'ilike', "%{$search}%")
                        ->orWhere('subject', 'ilike', "%{$search}%")
                        ->orWhere('recipient_name', 'ilike', "%{$search}%")
                        ->orWhereHas('tenant', fn ($tenant) => $tenant->where('name', 'ilike', "%{$search}%"))
                        ->orWhereHas('letterType', fn ($type) => $type->where('name', 'ilike', "%{$search}%"));
                });
            })->orWhereHas('requestedBy', fn ($user) => $user->where('name', 'ilike', "%{$search}%"));
        });
    }

    private function applySearch($query, array $columns, array $relations)
    {
        $search = trim($this->search);
        if ($search === '') {
            return $query;
        }

        return $query->where(function ($q) use ($search, $columns, $relations): void {
            foreach ($columns as $column) {
                $q->orWhere($column, 'ilike', "%{$search}%");
            }

            foreach ($relations as $relation) {
                $q->orWhereHas($relation, fn ($related) => $related->where('name', 'ilike', "%{$search}%"));
            }
        });
    }

    private function validateRequest(): void
    {
        $this->validate([
            'selectedLetterId' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:2000'],
            'statementFile' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);
    }

    private function normalizePerPage(int $value): int
    {
        return max(5, min($value, 50));
    }

    private function success(string $message): void
    {
        $this->dispatch('toast', type: 'success', message: $message);
    }

    private function domainError(\Throwable $exception, string $fallback): string
    {
        return $exception instanceof \DomainException ? $exception->getMessage() : $fallback;
    }

    public function render()
    {
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        return view('livewire.outgoing-letter-withdrawals.index', [
            'isSuperAdmin' => $isSuperAdmin,
            'issuedLetters' => $isSuperAdmin ? collect() : $this->issuedLettersQuery()->latest('issued_at')->paginate($this->perPage, ['*'], 'issuedPage'),
            'pendingRequests' => $isSuperAdmin ? $this->pendingRequestsQuery()->latest('requested_at')->paginate($this->pendingPerPage, ['*'], 'pendingPage') : collect(),
            'selectedLetter' => $this->selectedLetterId && ! $isSuperAdmin ? $this->issuedLettersQuery()->find($this->selectedLetterId) : null,
        ]);
    }
}
