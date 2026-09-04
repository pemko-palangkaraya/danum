<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return self::rulesFor($this->currentUser());
    }

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
        $this->replace(app(UserRoleAssignmentService::class)->normalize($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->currentUser();
            if ($user === null) {
                return;
            }

            $platformRole = $this->input('platform_role', $user->platform_role?->value);
            $tenantId = $this->input('tenant_id', $user->tenant_id);
            $customRoleId = $this->input('custom_role_id', $user->custom_role_id);

            if ($platformRole === 'super_admin' && ($tenantId !== null || $customRoleId !== null)) {
                $validator->errors()->add('platform_role', 'Super Admin tidak boleh memiliki tenant atau RBAC role.');
            }

            if ($platformRole === null && $tenantId === null) {
                $validator->errors()->add('tenant_id', 'Tenant member harus memiliki tenant.');
            }

            if ($platformRole === null && $tenantId !== null && $customRoleId === null) {
                $validator->errors()->add('custom_role_id', 'Tenant member harus memiliki RBAC role.');
            }

            if ($customRoleId !== null && $tenantId !== null && Role::findActiveForTenant($customRoleId, $tenantId) === null) {
                $validator->errors()->add('custom_role_id', 'RBAC role tidak berlaku untuk tenant yang dipilih.');
            }
        });
    }

    private function currentUser(): ?User
    {
        $routeUser = $this->route('user') ?? $this->route('id');
        if ($routeUser instanceof User) {
            return $routeUser;
        }

        $userId = $routeUser ?? $this->input('user_id');
        return $userId === null ? null : User::query()->find($userId);
    }
}
