<?php

declare(strict_types=1);

namespace App\Livewire\AuditLogs;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $actor = '';
    public string $tenant = '';
    public string $action = '';
    public string $object = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 15;

    #[On('outgoing-letters-refresh')]
    public function refreshForRealtime(): void
    {
        // The next render reloads the latest audit records using the active filters.
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

    public function with(): array
    {
        $logs = AuditLog::query()
            ->with([
                'user:id,name,email,tenant_id',
                'tenant:id,name,code',
            ])
            ->when($this->actor !== '', fn ($query) => $query->where('user_id', $this->actor))
            ->when($this->tenant !== '', fn ($query) => $query->where('tenant_id', $this->tenant))
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->object !== '', function ($query) {
                $value = '%' . trim($this->object) . '%';

                $query->where(function ($objectQuery) use ($value) {
                    $objectQuery
                        ->where('auditable_type', 'like', $value)
                        ->orWhere('auditable_id', 'like', $value);
                });
            })
            ->when($this->search !== '', function ($query) {
                $value = '%' . trim($this->search) . '%';

                $query->where(function ($searchQuery) use ($value) {
                    $searchQuery
                        ->where('action', 'like', $value)
                        ->orWhere('auditable_type', 'like', $value)
                        ->orWhere('auditable_id', 'like', $value)
                        ->orWhere('ip_address', 'like', $value)
                        ->orWhereHas('user', function ($userQuery) use ($value) {
                            $userQuery
                                ->where('name', 'like', $value)
                                ->orWhere('email', 'like', $value);
                        })
                        ->orWhereHas('tenant', function ($tenantQuery) use ($value) {
                            $tenantQuery
                                ->where('name', 'like', $value)
                                ->orWhere('code', 'like', $value);
                        });
                });
            })
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->dateTo))
            ->latest('created_at')
            ->paginate($this->perPage);

        return [
            'logs' => $logs,
            'actors' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'tenants' => Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'actions' => AuditLog::query()
                ->select('action')
                ->whereNotNull('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
        ];
    }

    public function render()
    {
        return view('livewire.pages.audit-logs.index');
    }
}
