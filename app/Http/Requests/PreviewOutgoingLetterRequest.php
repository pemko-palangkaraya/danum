<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LetterTypeStatus;
use App\Enums\PositionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewOutgoingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'letter_type_id' => [
                'required', 'uuid',
                Rule::exists('letter_types', 'id')->where(function ($query): void {
                    $query->where('tenant_id', $this->user()->tenant_id)->where('status', LetterTypeStatus::ACTIVE->value)->whereNull('deleted_at');
                }),
            ],
            'signer_position_id' => [
                'sometimes', 'nullable', 'uuid',
                Rule::exists('positions', 'id')->where(function ($query): void {
                    $query->where('tenant_id', $this->user()->tenant_id)->where('status', PositionStatus::ACTIVE->value)->where('can_sign', true)->whereNull('deleted_at');
                }),
            ],
            'number' => ['required', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_address' => ['nullable', 'string'],
            'subject' => ['required', 'string', 'max:255'],
        ];
    }
}
