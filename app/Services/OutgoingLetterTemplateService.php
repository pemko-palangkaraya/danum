<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\Tenant;
use InvalidArgumentException;

class OutgoingLetterTemplateService
{
    /** @var array<string, string> */
    private const VARIABLES = [
        'number' => 'Nomor surat',
        'recipient_name' => 'Nama penerima',
        'recipient_address' => 'Alamat penerima',
        'subject' => 'Perihal',
        'tenant_name' => 'Nama organisasi',
        'tenant_city' => 'Kota organisasi',
        'tenant_head_name' => 'Nama kepala organisasi',
    ];

    public function render(LetterType $letterType, Tenant $tenant, array $data): string
    {
        $template = (string) $letterType->body_template;
        $this->validate($template);

        return strtr($template, [
            '{{number}}' => (string) ($data['number'] ?? ''),
            '{{recipient_name}}' => (string) ($data['recipient_name'] ?? ''),
            '{{recipient_address}}' => (string) ($data['recipient_address'] ?? ''),
            '{{subject}}' => (string) ($data['subject'] ?? ''),
            '{{tenant_name}}' => $tenant->name,
            '{{tenant_city}}' => $tenant->city,
            '{{tenant_head_name}}' => (string) ($tenant->head_name ?? ''),
        ]);
    }

    /** @return array<string, string> */
    public function variables(): array
    {
        return self::VARIABLES;
    }

    public function validate(string $template): void
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $template, $matches);

        foreach (array_unique($matches[1] ?? []) as $variable) {
            if (! array_key_exists($variable, self::VARIABLES)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown letter template variable: %s.',
                    $variable,
                ));
            }
        }
    }
}
