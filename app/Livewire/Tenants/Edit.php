<?php

declare(strict_types=1);

namespace App\Livewire\Tenants;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Http\Requests\UpdateTenantRequest;
use App\Services\TenantService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Edit extends Component
{
    public string $tenantId = '';
    public string $code = '';
    public string $name = '';
    public string $tenant_category_id = '';
    public string $parent_tenant_id = '';
    public string $status = '';

    public string $administratorName = '';
    public string $administratorEmail = '';
    public string $administratorPassword = '';
    public string $administratorPasswordConfirmation = '';
    public string $administratorStatus = '';
    public ?int $administratorId = null;

    public function mount(TenantService $tenantService, string $tenant): void
    {
        $this->tenantId = $tenant;
        $model = $tenantService->find($tenant);
        abort_unless($model, 404);
        $this->authorize('update', $model);

        $this->code = (string) ($model->code ?? '');
        $this->name = (string) ($model->name ?? '');
        $this->tenant_category_id = (string) ($model->tenant_category_id ?? '');
        $this->parent_tenant_id = (string) ($model->parent_tenant_id ?? '');
        $this->status = (string) ($model->status?->value ?? $model->status ?? '');

        $administrator = $model->administrator;
        if ($administrator) {
            $this->administratorId = $administrator->id;
            $this->administratorName = $administrator->name;
            $this->administratorEmail = $administrator->email;
            $this->administratorStatus = (string) ($administrator->status?->value ?? $administrator->status ?? '');
        }
    }

    public function updatedTenantCategoryId(): void
    {
        $this->parent_tenant_id = '';
    }

    public function save(TenantService $tenantService): void
    {
        $tenant = $tenantService->find($this->tenantId);
        abort_unless($tenant, 404);
        $this->authorize('update', $tenant);

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'tenant_category_id' => $this->tenant_category_id,
            'parent_tenant_id' => $this->parent_tenant_id ?: null,
            'status' => $this->status,
        ];

        $rules = (new UpdateTenantRequest())->rules();
        $rules['code'] = [
            'sometimes', 'required', 'string', 'max:50',
            Rule::unique('tenants', 'code')->ignore($tenant->id),
        ];
        $validated = Validator::make($data, $rules)->validate();

        if ($this->administratorId !== null) {
            $administrator = Validator::make([
                'name' => $this->administratorName,
                'email' => $this->administratorEmail,
                'password' => $this->administratorPassword ?: null,
                'password_confirmation' => $this->administratorPasswordConfirmation,
                'status' => $this->administratorStatus,
            ], [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->administratorId)],
                'password' => ['nullable', 'string', 'min:8'],
                'password_confirmation' => ['same:password'],
                'status' => [Rule::enum(UserStatus::class)],
            ])->validate();
            $validated['_administrator'] = $administrator;
        }

        $tenantService->update($tenant, $validated);
        session()->flash('toast', ['type' => 'success', 'message' => 'Tenant berhasil diperbarui.']);
        $this->redirectRoute('tenants.index');
    }

    public function cancel(): void
    {
        $this->redirectRoute('tenants.index');
    }

    public function render(TenantService $tenantService)
    {
        return view('livewire.pages.tenants.edit', [
            'categories' => $tenantService->categories(),
            'parentTenants' => $tenantService->parentOptions($this->tenant_category_id, $this->tenantId),
            'statuses' => TenantStatus::cases(),
            'userStatuses' => UserStatus::cases(),
        ]);
    }
}
