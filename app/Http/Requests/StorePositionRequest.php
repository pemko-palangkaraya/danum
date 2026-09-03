<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PositionStatus;
use App\Enums\PositionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position_type' => ['sometimes', new Enum(PositionType::class)],
            'parent_id' => ['sometimes', 'nullable', 'uuid'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', new Enum(PositionStatus::class)],
            'can_sign' => ['boolean'],
            'can_validate' => ['boolean'],
        ];
    }
}
