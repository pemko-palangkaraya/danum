<?php

namespace App\Livewire\Tenants;

use App\Services\TenantService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filter = 'active';

    public int $perPage = 5;

    public ?string $selectedTenantId = null;

    public bool $showDeleteConfirmation = false;

    public bool $showRestoreConfirmation = false;

    public function confirmDelete(string $tenantId): void
    {
        $this->selectedTenantId = $tenantId;
        $this->showDeleteConfirmation = true;
    }

    public function confirmRestore(string $tenantId): void
    {
        $this->selectedTenantId = $tenantId;
        $this->showRestoreConfirmation = true;

        $this->dispatch(
            'open-confirmation-modal',
            id: 'tenant-restore',
        );
    }

    public function cancelDelete(): void
    {
        $this->selectedTenantId = null;
        $this->showDeleteConfirmation = false;
    }

    public function cancelRestore(): void
    {
        $this->selectedTenantId = null;
        $this->showRestoreConfirmation = false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function delete(TenantService $tenantService): void
    {
        if (!$this->selectedTenantId) {
            return;
        }

        $tenant = $tenantService->find($this->selectedTenantId);

        abort_unless($tenant, 404);

        $this->authorize('delete', $tenant);

        $tenantService->delete($tenant);

        $this->selectedTenantId = null;
        $this->showDeleteConfirmation = false;

        $this->dispatch(
            'toast',
            type: 'success',
            message: __('Tenant deleted successfully.'),
        );
    }

    public function with(TenantService $tenantService): array
    {
        return [
            'tenants' => $tenantService->search(
                search: $this->search,
                onlyDeleted: $this->filter === 'deleted',
                perPage: $this->perPage,
            ),
        ];
    }

    public function restoreTenant(TenantService $tenantService): void
    {
        if (!$this->selectedTenantId) {
            return;
        }

        $tenant = $tenantService->findWithTrashed(
            $this->selectedTenantId
        );

        abort_unless($tenant, 404);

        $this->authorize('restore', $tenant);

        $tenantService->restore($tenant);

        $this->selectedTenantId = null;
        $this->showRestoreConfirmation = false;

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Tenant restored successfully.',
        );
    }

    public function render()
    {
        return view('livewire.pages.tenants.index');
    }
}
