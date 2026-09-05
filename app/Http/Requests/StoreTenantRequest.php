<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TenantStatus;
use App\Models\TenantCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('tenants', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'tenant_category_id' => ['required', 'integer', Rule::exists('tenant_categories', 'id')->where('is_active', true)],
            'parent_tenant_id' => ['nullable', 'uuid', Rule::exists('tenants', 'id')->where('status', TenantStatus::ACTIVE->value)],
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
