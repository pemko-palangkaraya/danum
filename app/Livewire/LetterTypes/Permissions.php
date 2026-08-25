<?php

declare(strict_types=1);

namespace App\Livewire\LetterTypes;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Services\AuditLogService;
use App\Services\LetterTypeService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Permissions extends Component
{
    public string $letterTypeId;
    public string $search = '';
    public ?string $selectedTenantId = null;
    public string $selectedTenantName = '';

    public function mount(string $letterType): void
    {
        $this->letterTypeId = $letterType;
        $this->authorize('view', $this->letterType());
    }

    public function grant(string $tenantId, LetterTypeService $service, AuditLogService $auditLog): void
    {
        $letterType = $this->letterType();
        $this->authorize('update', $letterType);

        $tenant = Tenant::query()->findOrFail($tenantId);
        $existing = $letterType->permissions()->where('tenant_id', $tenant->id)->first();
        $permission = $service->grantTenantPermission($letterType, $tenant->id);

        if (! $existing || ! $existing->allowed) {
            $auditLog->record(
                'letter_type.permission.granted',
                auth()->user(),
                $permission,
                $existing?->only(['tenant_id', 'letter_type_id', 'allowed']),
                $permission->only(['tenant_id', 'letter_type_id', 'allowed']),
                $tenant->id,
            );
        }

        $this->dispatch('toast', type: 'success', message: 'Akses jenis surat diberikan ke '.$tenant->name.'.');
    }

    public function confirmRevoke(string $tenantId): void
    {
        $this->letterType();
        $this->authorize('update', $this->letterType());

        $tenant = Tenant::query()->findOrFail($tenantId);

        $this->selectedTenantId = $tenant->id;
        $this->selectedTenantName = $tenant->name;

        $this->dispatch('open-confirmation-modal', id: 'letter-type-permission-revoke');
    }

    public function cancelRevoke(): void
    {
        $this->selectedTenantId = null;
        $this->selectedTenantName = '';
    }

    public function revoke(LetterTypeService $service, AuditLogService $auditLog): void
    {
        if (! $this->selectedTenantId) {
            return;
        }

        $letterType = $this->letterType();
        $this->authorize('update', $letterType);

        $tenantId = $this->selectedTenantId;
        $permission = $letterType->permissions()->where('tenant_id', $tenantId)->first();

        if (! $service->revokeTenantPermission($letterType, $tenantId)) {
            $this->selectedTenantId = null;
            $this->selectedTenantName = '';
            $this->dispatch('toast', type: 'error', message: 'Akses tidak ditemukan.');
            return;
        }

        if ($permission?->allowed) {
            $permission->refresh();
            $auditLog->record(
                'letter_type.permission.revoked',
                auth()->user(),
                $permission,
                ['tenant_id' => $tenantId, 'letter_type_id' => $letterType->id, 'allowed' => true],
                $permission->only(['tenant_id', 'letter_type_id', 'allowed']),
                $tenantId,
            );
        }

        $this->selectedTenantId = null;
        $this->selectedTenantName = '';
        $this->dispatch('toast', type: 'success', message: 'Akses jenis surat dicabut.');
    }

    private function letterType(): LetterType
    {
        $letterType = LetterType::query()->findOrFail($this->letterTypeId);
        abort_unless($letterType->isGlobal(), 404);

        return $letterType;
    }

    public function render()
    {
        $letterType = $this->letterType();
        $query = Tenant::query()->orderBy('name');

        if ($this->search !== '') {
            $value = '%'.trim($this->search).'%';
            $query->where(fn ($q) => $q
                ->where('name', 'like', $value)
                ->orWhere('code', 'like', $value));
        }

        $tenants = $query->get(['id', 'code', 'name', 'status']);
        $allowedTenantIds = $letterType->permissions()
            ->where('allowed', true)
            ->pluck('tenant_id')
            ->all();

        return view('livewire.pages.letter-types.permissions', [
            'letterType' => $letterType,
            'tenants' => $tenants,
            'allowedTenantIds' => $allowedTenantIds,
        ]);
    }
}
