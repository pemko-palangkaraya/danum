<?php

declare(strict_types=1);

namespace App\Livewire\AuditLogs;

use App\Livewire\Concerns\WithStandardTablePagination;
use App\Services\AuditLogService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithStandardTablePagination;

    public string $search = '';
    public string $actor = '';
    public string $tenant = '';
    public string $action = '';
    public string $object = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 5;

    #[On('outgoing-letters-refresh')]
    public function refreshForRealtime(): void
    {
        // Re-render with the current filters so newly recorded events appear immediately.
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActor(): void
    {
        $this->resetPage();
    }

    public function updatedTenant(): void
    {
        $this->resetPage();
    }

    public function updatedAction(): void
    {
        $this->resetPage();
    }

    public function updatedObject(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = max(5, min($this->perPage, 50));
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'actor',
            'tenant',
            'action',
            'object',
            'dateFrom',
            'dateTo',
        ]);

        $this->resetPage();
    }

    public function with(AuditLogService $auditLogs): array
    {
        return [
            'logs' => $auditLogs->paginate([
                'search' => $this->search,
                'actor' => $this->actor,
                'tenant' => $this->tenant,
                'action' => $this->action,
                'object' => $this->object,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
            ], $this->perPage),
            'actors' => $auditLogs->actors(),
            'tenants' => $auditLogs->tenants(),
            'actions' => $auditLogs->actions(),
        ];
    }

    public function render()
    {
        return view('livewire.pages.audit-logs.index');
    }
}
