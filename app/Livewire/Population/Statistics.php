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
        $user = auth()->user();
        abort_unless($user?->hasPermission('population.view'), 403);

        if (! $user->isSuperAdmin()) {
            abort_unless($user->tenant_id, 422);
            $this->selectedTenantId = $user->tenant_id;
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
        $user = auth()->user();
        abort_unless($user?->hasPermission('population.view'), 403);

        $isSuperAdmin = $user->isSuperAdmin();
        $tenantId = $isSuperAdmin ? $this->selectedTenantId : $user->tenant_id;
        abort_unless($isSuperAdmin || $tenantId, 422);

        $service = app(PopulationStatisticsService::class);
        $statistics = $service->summarize($tenantId);

        return view('livewire.population.statistics', [
            ...$statistics,
            'isSuperAdmin' => $isSuperAdmin,
            'tenants' => $isSuperAdmin ? $service->tenants() : collect(),
        ]);
    }
}
