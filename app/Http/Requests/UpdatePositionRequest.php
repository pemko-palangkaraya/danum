<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PositionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', new Enum(PositionStatus::class)],
            'can_sign' => ['sometimes', 'boolean'],
        ];
    }
}
