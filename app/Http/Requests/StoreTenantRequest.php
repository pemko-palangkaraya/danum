<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SUPER_ADMIN;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('tenants', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'logo' => ['nullable', 'string', 'max:255'],
            'head_name' => ['nullable', 'string', 'max:150'],
            'head_title' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::enum(TenantStatus::class)],
        ];
    }
}
