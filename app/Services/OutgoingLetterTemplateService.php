<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Models\Tenant;
use InvalidArgumentException;

class OutgoingLetterTemplateService
{
    /** @var array<string, string> */
    private const VARIABLES = [
        'number' => 'Nomor surat',
        'recipient_name' => 'Nama pemohon',
        'recipient_address' => 'Alamat pemohon',
        'subject' => 'Perihal',
        'tenant_name' => 'Nama organisasi',
        'tenant_city' => 'Kota organisasi',
        'tenant_head_name' => 'Nama kepala organisasi',
    ];

    public function render(LetterType $letterType, Tenant $tenant, array $data): string
    {
        return $this->renderTemplate((string) $letterType->body_template, $tenant, $data);
    }

    public function renderVersion(LetterTypeVersion $version, Tenant $tenant, array $data): string
    {
        return $this->renderTemplate($version->body_template, $tenant, $data);
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

    private function renderTemplate(string $template, Tenant $tenant, array $data): string
    {
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
}
