<?php

declare(strict_types=1);

namespace App\Livewire\LetterTypes;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Services\LetterTypeService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Permissions extends Component
{
    public string $letterTypeId;
    public string $search = '';

    public function mount(string $letterType): void
    {
        $this->letterTypeId = $letterType;
        $this->authorize('view', $this->letterType());
    }

    public function grant(string $tenantId, LetterTypeService $service): void
    {
        $letterType = $this->letterType();
        $this->authorize('update', $letterType);

        $tenant = Tenant::query()->findOrFail($tenantId);
        $service->grantTenantPermission($letterType, $tenant->id);

        $this->dispatch('toast', type: 'success', message: 'Akses jenis surat diberikan ke '.$tenant->name.'.');
    }

    public function revoke(string $tenantId, LetterTypeService $service): void
    {
        $letterType = $this->letterType();
        $this->authorize('update', $letterType);

        if (! $service->revokeTenantPermission($letterType, $tenantId)) {
            $this->dispatch('toast', type: 'error', message: 'Akses tidak ditemukan.');
            return;
        }

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
