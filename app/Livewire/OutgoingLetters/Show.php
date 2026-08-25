<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Enums\UserRole;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Services\VerificationQrCodeService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public OutgoingLetter $letter;
    public ?string $verificationQrCode = null;
    public array $contentParts = [];
    public Collection $history;
    public ?OutgoingLetterWithdrawalRequest $withdrawalDecision = null;

    public function mount(string $id, VerificationQrCodeService $qrCodeService): void
    {
        $this->loadLetter($id, $qrCodeService);
    }

    #[On('outgoing-letters-refresh')]
    public function refreshForRealtime(VerificationQrCodeService $qrCodeService): void
    {
        $this->loadLetter($this->letter->id, $qrCodeService);
    }

    private function loadLetter(string $id, VerificationQrCodeService $qrCodeService): void
    {
        $query = OutgoingLetter::query()
            ->with(['tenant', 'letterType', 'letterTypeVersion', 'withdrawalRequests.decidedBy']);

        if (auth()->user()->role === UserRole::SUPER_ADMIN) {
            $query->withTrashed();
        } else {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        $this->letter = $query->findOrFail($id);

        $this->authorize('view', $this->letter);

        $this->withdrawalDecision = $this->letter->withdrawalRequests
            ->first(fn (OutgoingLetterWithdrawalRequest $request) => $request->status !== OutgoingLetterWithdrawalStatus::PENDING);

        $this->contentParts = preg_split(
            '/(\{\{\s*tte\s*\}\})/i',
            (string) $this->letter->content,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        ) ?: [(string) $this->letter->content];

        $this->history = $this->letter
            ->statusHistories()
            ->with('changedBy')
            ->latest('created_at')
            ->get();

        $this->verificationQrCode = null;

        if ($this->letter->status->value === 'issued' && $this->letter->verification_token) {
            $this->verificationQrCode = $qrCodeService->render(
                route('verification.show', ['token' => $this->letter->verification_token]),
            );
        }
    }

    public function render()
    {
        return view('livewire.pages.outgoing-letters.show');
    }
}
