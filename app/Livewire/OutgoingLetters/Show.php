<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Enums\UserRole;
use App\Models\OutgoingLetter;
use App\Services\VerificationQrCodeService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public OutgoingLetter $letter;
    public ?string $verificationQrCode = null;
    public array $contentParts = [];
    public Collection $history;

    public function mount(string $id, VerificationQrCodeService $qrCodeService): void
    {
        $query = OutgoingLetter::query()
            ->with(['tenant', 'letterType', 'letterTypeVersion']);

        if (auth()->user()->role === UserRole::SUPER_ADMIN) {
            $query->withTrashed();
        } else {
            $query->where('tenant_id', auth()->user()->tenant_id);
        }

        $this->letter = $query->findOrFail($id);

        $this->authorize('view', $this->letter);

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
