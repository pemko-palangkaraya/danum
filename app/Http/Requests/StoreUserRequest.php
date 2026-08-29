<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isSuperAdmin() === true; }

    protected function prepareForValidation(): void
    {
        $role = $this->input('role');
        $tenantId = $this->input('tenant_id');
        $customRoleId = $this->input('custom_role_id');

        if ($role === 'super_admin') {
            $this->merge(['platform_role' => 'super_admin', 'tenant_id' => null, 'custom_role_id' => null]);
            return;
        }

        if ($tenantId !== null && $customRoleId === null && in_array($role, ['tenant_user', 'tenant_admin'], true)) {
            $defaultRole = Role::query()->where('tenant_id', $tenantId)->where('slug', $role)->where('is_active', true)->first();
            if ($defaultRole !== null) $this->merge(['platform_role' => null, 'custom_role_id' => $defaultRole->id]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', Rule::in(['super_admin', 'tenant_admin', 'tenant_user'])],
            'platform_role' => ['nullable', 'string', Rule::in(['super_admin'])],
            'tenant_id' => ['nullable', 'uuid', 'exists:tenants,id'],
            'custom_role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'status' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $platformRole = $this->input('platform_role');
            $tenantId = $this->input('tenant_id');
            $customRoleId = $this->input('custom_role_id');

            if ($platformRole === 'super_admin' && ($tenantId !== null || $customRoleId !== null)) {
                $validator->errors()->add('platform_role', 'Super Admin tidak boleh memiliki tenant atau RBAC role.');
            }
            if ($platformRole === null && $tenantId === null) {
                $validator->errors()->add('tenant_id', 'Tenant member harus memiliki tenant.');
            }
            if ($tenantId !== null && $customRoleId === null) {
                $validator->errors()->add('custom_role_id', 'Tenant member harus memiliki RBAC role.');
            }
            if ($customRoleId !== null && $tenantId !== null) {
                $valid = Role::query()->whereKey($customRoleId)->where('is_active', true)->where(function ($query) use ($tenantId) {
                    $query->where(fn ($q) => $q->where('scope', 'global')->whereNull('tenant_id'))
                        ->orWhere(fn ($q) => $q->where('scope', 'tenant')->where('tenant_id', $tenantId));
                })->exists();
                if (! $valid) $validator->errors()->add('custom_role_id', 'RBAC role tidak berlaku untuk tenant yang dipilih.');
            }
        });
    }
}
