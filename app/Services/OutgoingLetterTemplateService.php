<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\Tenant;

class OutgoingLetterTemplateService
{
    public function render(LetterType $letterType, Tenant $tenant, array $data): string
    {
        return strtr((string) $letterType->body_template, [
            '{{number}}' => $data['number'],
            '{{recipient_name}}' => $data['recipient_name'],
            '{{recipient_address}}' => $data['recipient_address'] ?? '',
            '{{subject}}' => $data['subject'],
            '{{tenant_name}}' => $tenant->name,
            '{{tenant_city}}' => $tenant->city,
            '{{tenant_head_name}}' => $tenant->head_name ?? '',
        ]);
    }
}
