<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
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
            'date' => 'Tanggal surat',
            'birth_date' => 'Tanggal lahir',
        ];
    }

    /** @return list<string> */
    public function normalizeVariables(string $input): array
    {
        $tokens = preg_split('/[\s,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $variables = [];
        foreach ($tokens as $token) {
            $variable = preg_replace('/^\{\{\s*|\s*\}\}$/', '', trim($token)) ?? trim($token);
            if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $variable)) $variables[] = $variable;
        }
        return array_values(array_unique($variables));
    }

    /** @return list<string> */
    public function extractVariables(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('File DOCX tidak dapat dibuka.');
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $text, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    /** @param list<string> $declared @param list<string> $found */
    public function compareVariables(array $declared, array $found): array
    {
        return ['missing' => array_values(array_diff($declared, $found)), 'unknown' => array_values(array_diff($found, $declared))];
    }

    /** @param list<string> $found @param list<string> $allowed */
    public function validateVariables(array $found, array $allowed): array
    {
        return ['missing' => array_values(array_diff($allowed, $found)), 'unknown' => array_values(array_diff($found, $allowed))];
    }

    public function renderToStorage(string $templatePath, Tenant $tenant, array $data): string
    {
        $source = new ZipArchive();
        if ($source->open($templatePath) !== true) throw new RuntimeException('File DOCX template tidak dapat dibuka.');
        $xml = $source->getFromName('word/document.xml');
        $source->close();
        if ($xml === false) throw new RuntimeException('DOCX tidak memiliki word/document.xml.');

        $values = [
            'tenant_name' => $tenant->name,
            'tenant_city' => $tenant->city,
            'tenant_head_name' => (string) ($tenant->head_name ?? ''),
            ...$data,
        ];
        $xml = preg_replace_callback('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', fn (array $m) => htmlspecialchars((string) ($values[$m[1]] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8'), $xml) ?? $xml;

        $tmp = tempnam(sys_get_temp_dir(), 'danum-docx-');
        if ($tmp === false || !copy($templatePath, $tmp)) throw new RuntimeException('Tidak dapat membuat DOCX hasil.');
        $output = new ZipArchive();
        if ($output->open($tmp) !== true) { @unlink($tmp); throw new RuntimeException('Tidak dapat membuka DOCX hasil.'); }
        $output->addFromString('word/document.xml', $xml);
        $output->close();

        $path = 'outgoing-letters/'.date('Y/m').'/'.uniqid('letter-', true).'.docx';
        Storage::disk('local')->put($path, file_get_contents($tmp));
        @unlink($tmp);
        return $path;
    }

    public function extractText(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('File DOCX tidak dapat dibuka.');
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();
        $xml = preg_replace('/<w:br\s*\/>/i', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<w:p[^>]*>/i', "\n", $xml) ?? $xml;
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }
}
