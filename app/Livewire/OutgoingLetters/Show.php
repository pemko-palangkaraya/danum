<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Models\OutgoingLetter;
use App\Services\VerificationQrCodeService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public OutgoingLetter $letter;
    public ?string $verificationQrCode = null;

    public function mount(string $id, VerificationQrCodeService $qrCodeService): void
    {
        $this->letter = OutgoingLetter::query()
            ->with(['tenant', 'letterType', 'letterTypeVersion'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $this->authorize('view', $this->letter);

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
