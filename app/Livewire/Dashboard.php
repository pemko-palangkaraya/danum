<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\DashboardService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('dashboard.view'), 403);
    }

    public function render(DashboardService $service)
    {
        return view('livewire.pages.dashboard', $service->summarize(auth()->user()));
    }
}
