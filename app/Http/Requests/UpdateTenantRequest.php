<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TenantStatus;
use App\Models\TenantCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('tenants', 'code')->ignore($this->route('tenant')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'tenant_category_id' => ['sometimes', 'required', 'integer', Rule::exists('tenant_categories', 'id')->where('is_active', true)],
            'parent_tenant_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('tenants', 'id')->where('status', true)],
            'province' => ['sometimes', 'required', 'string', 'max:100'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'district' => ['sometimes', 'required', 'string', 'max:100'],
            'village' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'head_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'head_title' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', Rule::enum(TenantStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $categoryCode = $this->tenant_category_id
            ? TenantCategory::query()->whereKey($this->tenant_category_id)->value('code')
            : null;

        if (! in_array($categoryCode, ['kecamatan', 'kelurahan', 'desa'], true)) {
            $this->merge(['parent_tenant_id' => null]);
        }
    }
}
