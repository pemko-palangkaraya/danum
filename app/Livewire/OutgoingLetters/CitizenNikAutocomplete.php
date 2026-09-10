<?php

declare(strict_types=1);

namespace App\Livewire\OutgoingLetters;

use App\Services\CitizenService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CitizenNikAutocomplete extends Component
{
    public string $search = '';
    public string $variable = 'recipient_nik';
    public bool $showSuggestions = false;

    public function mount(string $value = '', string $variable = 'recipient_nik'): void
    {
        $this->search = preg_replace('/\D+/', '', $value) ?? '';
        $this->variable = $variable;
    }

    public function updatedSearch(): void
    {
        $this->search = preg_replace('/\D+/', '', $this->search) ?? '';
        $this->showSuggestions = $this->search !== '';
    }

    public function selectCitizen(string $nik): void
    {
        $this->search = preg_replace('/\D+/', '', $nik) ?? '';
        $this->showSuggestions = false;
        $this->dispatch('citizen-nik-selected', variable: $this->variable, nik: $this->search);
    }

    public function render(CitizenService $citizenService): View
    {
        $tenantId = auth()->user()?->tenant_id;
        $suggestions = collect();

        if ($this->showSuggestions && $tenantId && strlen($this->search) >= 3 && strlen($this->search) <= 16) {
            $suggestions = $citizenService->query((string) $tenantId, '', null)
                ->where('nik', 'like', $this->search . '%')
                ->limit(8)
                ->get(['id', 'nik', 'nama_lengkap']);
        }

        return view('livewire.pages.outgoing-letters.partials.citizen-nik-autocomplete', [
            'suggestions' => $suggestions,
        ]);
    }

    // The parent component remains responsible for the actual citizen hydration.
}
