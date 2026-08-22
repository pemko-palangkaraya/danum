<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;
use ZipArchive;

class DocxTemplateService
{
    /** @return array<string,string> */
    public function allowedVariables(): array
    {
        return [
            'number' => 'Nomor surat',
            'recipient_name' => 'Nama penerima',
            'recipient_address' => 'Alamat penerima',
            'subject' => 'Perihal / keperluan',
            'tenant_name' => 'Nama instansi / tenant',
            'tenant_city' => 'Kota / wilayah tenant',
            'tenant_head_name' => 'Nama pejabat penandatangan',
        ];
    }

    /** @return list<string> */
    public function normalizeVariables(string $input): array
    {
        $tokens = preg_split('/[\s,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $variables = [];

        foreach ($tokens as $token) {
            $variable = trim($token);
            $variable = preg_replace('/^\{\{\s*|\s*\}\}$/', '', $variable) ?? $variable;

            if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $variable)) {
                $variables[] = $variable;
            }
        }

        return array_values(array_unique($variables));
    }

    /** @return list<string> */
    public function extractVariables(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File DOCX tidak dapat dibuka.');
        }
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $text, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    /** @param list<string> $declared @param list<string> $found */
    public function compareVariables(array $declared, array $found): array
    {
        return [
            'missing' => array_values(array_diff($declared, $found)),
            'unknown' => array_values(array_diff($found, $declared)),
        ];
    }

    /** @param list<string> $found @param list<string> $allowed */
    public function validateVariables(array $found, array $allowed): array
    {
        return [
            'missing' => array_values(array_diff($allowed, $found)),
            'unknown' => array_values(array_diff($found, $allowed)),
        ];
    }
}
