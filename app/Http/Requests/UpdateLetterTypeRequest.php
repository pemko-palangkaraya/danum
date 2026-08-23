<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LetterTypeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLetterTypeRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'tenant_id' => ['prohibited'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('letter_types', 'code')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('id'))],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'body_template' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::enum(LetterTypeStatus::class)],
            'has_expiry' => ['sometimes', 'boolean'],
            'validity_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'required_if:has_expiry,true'],
        ];
    }
}
