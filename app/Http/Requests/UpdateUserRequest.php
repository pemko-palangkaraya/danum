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
        $routeUser = $this->route('user');
        $userId = $routeUser instanceof User ? $routeUser->getKey() : $routeUser;
        $userId ??= $this->input('user_id');

        $currentUser = $userId !== null ? User::query()->find($userId) : null;
        $email = $this->input('email');

        $emailRules = ['sometimes', 'required', 'email', 'max:255'];

        // Livewire does not have a route parameter. When the submitted email is
        // the user's existing email, do not run the unique check at all.
        if (
            $currentUser === null
            || $email === null
            || strcasecmp(trim((string) $currentUser->email), trim((string) $email)) !== 0
        ) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($userId);
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'nip' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => $emailRules,
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', Rule::enum(UserRole::class)],
            'tenant_id' => ['sometimes', 'nullable', 'uuid', 'exists:tenants,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $routeUser = $this->route('user');
            $userId = $routeUser instanceof User ? $routeUser->getKey() : $routeUser;
            $userId ??= $this->input('user_id');
            $user = $userId === null ? null : User::query()->find($userId);

            if ($user === null) {
                return;
            }

            $role = $this->input('role', $user->role->value);
            $tenantId = $this->has('tenant_id')
                ? $this->input('tenant_id')
                : $user->tenant_id;

            if (in_array($role, [UserRole::TENANT_USER->value, UserRole::TENANT_ADMIN->value], true) && $tenantId === null) {
                $validator->errors()->add('tenant_id', 'Tenant user harus memiliki organisasi.');
            }

            if ($role === UserRole::SUPER_ADMIN->value && $tenantId !== null) {
                $validator->errors()->add('tenant_id', 'Super Admin tidak boleh memiliki organisasi.');
            }
        });
    }
}
