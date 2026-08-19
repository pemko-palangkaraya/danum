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
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'tenant_id' => ['sometimes', 'nullable', 'uuid', 'exists:tenants,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = User::query()->find($this->route('user'));

            if ($user === null) {
                return;
            }

            $role = $this->input('role', $user->role->value);
            $tenantId = $this->has('tenant_id')
                ? $this->input('tenant_id')
                : $user->tenant_id;

            if ($role === UserRole::TENANT_USER->value && $tenantId === null) {
                $validator->errors()->add(
                    'tenant_id',
                    'A tenant user must belong to a tenant.',
                );
            }

            if ($role === UserRole::SUPER_ADMIN->value && $tenantId !== null) {
                $validator->errors()->add(
                    'tenant_id',
                    'A super admin cannot belong to a tenant.',
                );
            }
        });
    }
}
