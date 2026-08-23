<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LetterTypeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterTypeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'tenant_id' => ['prohibited'],
            'code' => ['required', 'string', 'max:50', Rule::unique('letter_types', 'code')->where('tenant_id', $this->user()->tenant_id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'body_template' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(LetterTypeStatus::class)],
            'has_expiry' => ['sometimes', 'boolean'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'required_if:has_expiry,true'],
        ];
    }
}
