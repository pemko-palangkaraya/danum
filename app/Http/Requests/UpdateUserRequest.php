<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->isSuperAdmin() === true; }
    public function rules(): array { return self::rulesFor($this->currentUser()); }

    public static function rulesFor(?User $user): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'nip' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->getKey())],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'nullable', Rule::in(['super_admin', 'tenant_admin', 'tenant_user'])],
            'platform_role' => ['sometimes', 'nullable', 'string', Rule::in(['super_admin'])],
            'tenant_id' => ['sometimes', 'nullable', 'uuid', 'exists:tenants,id'],
            'custom_role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'status' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('role') === 'super_admin') {
            $this->merge(['platform_role' => 'super_admin', 'tenant_id' => null, 'custom_role_id' => null]);
            return;
        }

        if ($this->input('role') && $this->input('tenant_id') && ! $this->input('custom_role_id')) {
            $role = Role::query()->where('tenant_id', $this->input('tenant_id'))->where('slug', $this->input('role'))->where('is_active', true)->first();
            if ($role) $this->merge(['platform_role' => null, 'custom_role_id' => $role->id]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->currentUser();
            if ($user === null) return;
            $platformRole = $this->has('platform_role') ? $this->input('platform_role') : $user->platform_role?->value;
            $tenantId = $this->has('tenant_id') ? $this->input('tenant_id') : $user->tenant_id;
            $customRoleId = $this->has('custom_role_id') ? $this->input('custom_role_id') : $user->custom_role_id;
            if ($platformRole === 'super_admin' && ($tenantId !== null || $customRoleId !== null)) $validator->errors()->add('platform_role', 'Super Admin tidak boleh memiliki tenant atau RBAC role.');
            if ($platformRole === null && $tenantId === null) $validator->errors()->add('tenant_id', 'Tenant member harus memiliki tenant.');
            if ($platformRole === null && $tenantId !== null && $customRoleId === null) $validator->errors()->add('custom_role_id', 'Tenant member harus memiliki RBAC role.');
            if ($customRoleId !== null && $tenantId !== null) {
                $valid = Role::query()->whereKey($customRoleId)->where('is_active', true)->where(function ($query) use ($tenantId) {
                    $query->where(fn ($q) => $q->where('scope', 'global')->whereNull('tenant_id'))->orWhere(fn ($q) => $q->where('scope', 'tenant')->where('tenant_id', $tenantId));
                })->exists();
                if (! $valid) $validator->errors()->add('custom_role_id', 'RBAC role tidak berlaku untuk tenant yang dipilih.');
            }
        });
    }

    private function currentUser(): ?User
    {
        $routeUser = $this->route('user') ?? $this->route('id');
        if ($routeUser instanceof User) return $routeUser;
        $userId = $routeUser ?? $this->input('user_id');
        return $userId === null ? null : User::query()->find($userId);
    }
}
