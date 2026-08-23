<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SUPER_ADMIN;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'nip' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'tenant_id' => ['sometimes', 'nullable', 'uuid', 'exists:tenants,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = User::query()->find($this->route('id'));

            if ($user === null) {
                return;
            }

            $role = $this->input('role', $user->role->value);
            $tenantId = $this->has('tenant_id')
                ? $this->input('tenant_id')
                : $user->tenant_id;

            if (in_array($role, [UserRole::TENANT_USER->value, UserRole::TENANT_ADMIN->value], true) && $tenantId === null) {
                $validator->errors()->add(
                    'tenant_id',
                    'Tenant user harus memiliki organisasi.',
                );
            }

            if ($role === UserRole::SUPER_ADMIN->value && $tenantId !== null) {
                $validator->errors()->add(
                    'tenant_id',
                    'Super Admin tidak boleh memiliki organisasi.',
                );
            }
        });
    }
}
