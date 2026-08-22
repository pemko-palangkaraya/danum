<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Models\OutgoingLetter;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public OutgoingLetter $letter;

    public function mount(string $id): void
    {
        $this->letter = OutgoingLetter::query()
            ->with(['tenant', 'letterType', 'letterTypeVersion'])
            ->where('tenant_id', auth()->user()->tenant_id)
            ->findOrFail($id);

        $this->authorize('view', $this->letter);
    }

    public function render()
    {
        return view('livewire.pages.outgoing-letters.show');
    }
}
