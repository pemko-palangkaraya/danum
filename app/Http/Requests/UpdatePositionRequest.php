<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PositionStatus;
use App\Enums\PositionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'position_type' => ['sometimes', new Enum(PositionType::class)],
            'parent_id' => ['sometimes', 'nullable', 'uuid'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'status' => ['sometimes', new Enum(PositionStatus::class)],
            'can_sign' => ['sometimes', 'boolean'],
            'can_validate' => ['sometimes', 'boolean'],
        ];
    }
}
