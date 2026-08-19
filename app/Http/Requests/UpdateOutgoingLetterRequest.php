<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\LetterTypeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOutgoingLetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['prohibited'],
            'letter_type_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('letter_types', 'id')->where(function ($query): void {
                    $query
                        ->where('tenant_id', $this->user()->tenant_id)
                        ->where('status', LetterTypeStatus::ACTIVE->value)
                        ->whereNull('deleted_at');
                }),
            ],
            'number' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('outgoing_letters', 'number')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($this->route('outgoing_letter')),
            ],
            'recipient_name' => ['sometimes', 'required', 'string', 'max:150'],
            'recipient_address' => ['sometimes', 'nullable', 'string'],
            'subject' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'issued_at' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
