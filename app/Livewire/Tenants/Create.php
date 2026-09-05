<?php

declare(strict_types=1);

namespace App\Livewire\Tenants;

use App\Enums\TenantStatus;
use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

    public string $code = '';
    public string $name = '';
    public string $tenant_category_id = '';
    public string $parent_tenant_id = '';
    public string $province = '';
    public string $city = '';
    public string $district = '';
    public string $village = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $logo = '';
    public string $head_name = '';
    public string $head_title = '';
    public string $status = '';
    public $letterhead = null;

    public string $admin_name = '';
    public string $admin_email = '';
    public string $admin_password = '';
    public string $admin_password_confirmation = '';

    public function mount(): void
    {
        $this->authorize('create', Tenant::class);
        $this->status = (string) TenantStatus::ACTIVE->value;
    }

    public function updatedTenantCategoryId(): void
    {
        $this->parent_tenant_id = '';
    }

    public function updatedParentTenantId(TenantService $tenantService): void
    {
        if ($this->parent_tenant_id === '') {
            return;
        }

        $parent = $tenantService->find($this->parent_tenant_id);
        if (! $parent) {
            return;
        }

        $this->province = (string) $parent->province;
        $this->city = (string) $parent->city;

        if ($parent->district !== 'Pusat Pemerintahan') {
            $this->district = (string) $parent->district;
        }
    }

    public function save(TenantService $tenantService): void
    {
        $this->authorize('create', Tenant::class);

        $tenantData = [
            'code' => $this->code,
            'name' => $this->name,
            'tenant_category_id' => $this->tenant_category_id,
            'parent_tenant_id' => $this->parent_tenant_id ?: null,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'village' => $this->village,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo' => $this->logo,
            'head_name' => $this->head_name,
            'head_title' => $this->head_title,
            'status' => $this->status,
        ];

        $validated = Validator::make($tenantData, (new StoreTenantRequest())->rules())->validate();

        $this->validate([
            'letterhead' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validated['letterhead_path'] = $this->letterhead->store('tenant-letterheads', 'public');

        $tenantService->createWithInitialUser($validated, [
            'name' => $this->admin_name,
            'email' => $this->admin_email,
            'password' => $this->admin_password,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Tenant dan administrator berhasil dibuat.');
        $this->redirectRoute('tenants.index');
    }

    public function cancel(): void
    {
        $this->redirectRoute('tenants.index');
    }

    public function render(TenantService $tenantService)
    {
        return view('livewire.pages.tenants.create', [
            'categories' => $tenantService->categories(),
            'parentTenants' => $tenantService->parentOptions($this->tenant_category_id),
            'statuses' => TenantStatus::cases(),
        ]);
    }
}
