<?php

declare(strict_types=1);

namespace App\Livewire\LetterTypes;

use App\Livewire\Concerns\WithStandardTablePagination;
use App\Models\LetterType;
use App\Models\TenantCategory;
use App\Services\AuditLogService;
use App\Services\LetterTypeService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Permissions extends Component
{
    use WithStandardTablePagination;

    public string $search = '';
    public int $perPage = 5;
    public string $letterTypeId;
    public ?int $selectedCategoryId = null;
    public string $selectedCategoryName = '';

    public function mount(string $letterType): void
    {
        $this->letterTypeId = $letterType;
        $this->authorize('view', $this->letterType());
    }

    public function grantCategory(int $categoryId, LetterTypeService $service, AuditLogService $auditLog): void
    {
        $letterType = $this->authorizedLetterType();
        $category = TenantCategory::query()->where('is_active', true)->findOrFail($categoryId);
        $existing = $letterType->permissions()->whereNull('tenant_id')->where('tenant_category_id', $category->id)->first();
        $permission = $service->grantCategoryPermission($letterType, $category->id);

        if (! $existing || ! $existing->allowed) {
            $this->recordAudit($auditLog, 'letter_type.category_permission.granted', $permission, $existing?->only(['tenant_category_id', 'letter_type_id', 'allowed']), $permission->only(['tenant_category_id', 'letter_type_id', 'allowed']));
        }

        $this->dispatch('toast', type: 'success', message: 'Akses jenis surat diberikan ke kategori ' . $category->name . '.');
    }

    public function confirmRevokeCategory(int $categoryId): void
    {
        $this->authorizedLetterType();
        $category = TenantCategory::query()->where('is_active', true)->findOrFail($categoryId);
        $this->selectedCategoryId = $category->id;
        $this->selectedCategoryName = $category->name;
        $this->dispatch('open-confirmation-modal', id: 'letter-type-category-permission-revoke');
    }

    public function cancelRevokeCategory(): void
    {
        $this->selectedCategoryId = null;
        $this->selectedCategoryName = '';
    }

    public function revokeCategory(LetterTypeService $service, AuditLogService $auditLog): void
    {
        if ($this->selectedCategoryId === null) {
            return;
        }

        $letterType = $this->authorizedLetterType();
        $categoryId = $this->selectedCategoryId;
        $permission = $letterType->permissions()->whereNull('tenant_id')->where('tenant_category_id', $categoryId)->first();

        if (! $service->revokeCategoryPermission($letterType, $categoryId)) {
            $this->cancelRevokeCategory();
            $this->dispatch('toast', type: 'error', message: 'Akses kategori tidak ditemukan.');
            return;
        }

        if ($permission?->allowed) {
            $permission->refresh();
            $this->recordAudit($auditLog, 'letter_type.category_permission.revoked', $permission, ['tenant_category_id' => $categoryId, 'letter_type_id' => $letterType->id, 'allowed' => true], $permission->only(['tenant_category_id', 'letter_type_id', 'allowed']));
        }

        $this->cancelRevokeCategory();
        $this->dispatch('toast', type: 'success', message: 'Akses kategori dicabut.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = max(5, min($this->perPage, 100));
        $this->resetPage();
    }

    private function letterType(): LetterType
    {
        $letterType = LetterType::query()->findOrFail($this->letterTypeId);
        abort_unless($letterType->isGlobal(), 404);
        return $letterType;
    }

    private function authorizedLetterType(): LetterType
    {
        $letterType = $this->letterType();
        $this->authorize('update', $letterType);
        return $letterType;
    }

    private function recordAudit(AuditLogService $auditLog, string $action, object $auditable, ?array $oldValues, array $newValues): void
    {
        $auditLog->record($action, auth()->user(), $auditable, $oldValues, $newValues);
    }

    public function render()
    {
        $letterType = $this->letterType();
        $search = trim($this->search);
        $query = TenantCategory::query()
            ->where('is_active', true)
            ->withCount(['tenants as active_tenants_count' => fn ($query) => $query->where('status', true)])
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($search !== '') {
            $value = '%' . $search . '%';
            $query->where(fn ($query) => $query
                ->where('name', 'like', $value)
                ->orWhere('code', 'like', $value));
        }

        return view('livewire.pages.letter-types.permissions', [
            'letterType' => $letterType,
            'categories' => $query->paginate($this->perPage),
            'allowedCategoryIds' => $letterType->permissions()
                ->where('allowed', true)
                ->whereNotNull('tenant_category_id')
                ->pluck('tenant_category_id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        ]);
    }
}
