<?php

declare(strict_types=1);

namespace App\Livewire\Tenants;

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Http\Requests\UpdateTenantRequest;
use App\Services\TenantService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Edit extends Component
{
    use WithFileUploads;

    public string $tenantId = '';
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
    public ?string $currentLetterhead = null;

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
        $this->province = (string) ($model->province ?? '');
        $this->city = (string) ($model->city ?? '');
        $this->district = (string) ($model->district ?? '');
        $this->village = (string) ($model->village ?? '');
        $this->address = (string) ($model->address ?? '');
        $this->phone = (string) ($model->phone ?? '');
        $this->email = (string) ($model->email ?? '');
        $this->logo = (string) ($model->logo ?? '');
        $this->head_name = (string) ($model->head_name ?? '');
        $this->head_title = (string) ($model->head_title ?? '');
        $this->status = (string) ($model->status?->value ?? $model->status ?? '');
        $this->currentLetterhead = $model->letterheadUrl();

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
        $tenant = $tenantService->find($this->tenantId);
        abort_unless($tenant, 404);
        $this->authorize('update', $tenant);

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'tenant_category_id' => $this->tenant_category_id,
            'parent_tenant_id' => $this->parent_tenant_id ?: null,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'village' => $this->village,
            'address' => $this->address ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'logo' => $this->logo ?: null,
            'head_name' => $this->head_name ?: null,
            'head_title' => $this->head_title ?: null,
            'status' => $this->status,
        ];

        $rules = (new UpdateTenantRequest())->rules();
        $rules['code'] = [
            'sometimes', 'required', 'string', 'max:50',
            Rule::unique('tenants', 'code')->ignore($tenant->id),
        ];
        $validated = Validator::make($data, $rules)->validate();

        if ($this->letterhead) {
            $this->validate(['letterhead' => ['file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096']]);
            $oldLetterhead = $tenant->letterhead_path;
            $newLetterhead = $this->letterhead->store('tenant-letterheads', 'public');
            $validated['letterhead_path'] = $newLetterhead;
            if ($oldLetterhead && $oldLetterhead !== $newLetterhead) {
                Storage::disk('public')->delete($oldLetterhead);
            }
        }

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
