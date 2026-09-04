<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Services\PopulationStatisticsService;
use Livewire\Component;

class Statistics extends Component
{
    public ?string $selectedTenantId = null;
    public bool $showAgePyramid = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);

        if (! auth()->user()->isSuperAdmin()) {
            $this->selectedTenantId = auth()->user()->tenant_id;
        }
    }

    public function openAgePyramid(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);
        $this->showAgePyramid = true;
    }

    public function closeAgePyramid(): void
    {
        $this->showAgePyramid = false;
    }

    public function render()
    {
        abort_unless(auth()->user()?->hasPermission('population.view'), 403);

        $user = auth()->user();
        $tenantId = $user->isSuperAdmin() ? $this->selectedTenantId : $user->tenant_id;
        $service = app(PopulationStatisticsService::class);
        $statistics = $service->summarize($tenantId);

        return view('livewire.population.statistics', [
            ...$statistics,
            'tenants' => $user->isSuperAdmin() ? $service->tenants() : collect(),
        ]);
    }
}
