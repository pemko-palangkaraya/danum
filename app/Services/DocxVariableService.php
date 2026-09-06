<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\LetterVariableSchema;
use RuntimeException;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class DocxVariableService
{
    /** @return array<string,string> */
    public function allowedVariables(): array
    {
        return [
            'number' => 'Nomor surat', 'recipient_name' => 'Nama pemohon', 'recipient_address' => 'Alamat pemohon',
            'recipient_birth_place' => 'Tempat lahir', 'recipient_birth_date' => 'Tanggal lahir', 'recipient_nik' => 'NIK',
            'subject' => 'Perihal / keperluan', 'tenant_name' => 'Nama instansi / tenant', 'tenant_city' => 'Kota / wilayah tenant',
            'tenant_district' => 'Kecamatan tenant', 'tenant_village' => 'Kelurahan / desa tenant', 'tenant_province' => 'Provinsi tenant',
            'tenant_address' => 'Alamat tenant', 'tenant_phone' => 'Telepon tenant', 'tenant_email' => 'Email tenant',
            'tenant_head_name' => 'Nama pejabat penandatangan', 'tenant_head_title' => 'Jabatan pejabat penandatangan',
            'nama_ttd' => 'Nama pejabat penandatangan', 'jabatan_ttd' => 'Jabatan pejabat penandatangan',
            'date' => 'Tanggal surat', 'letterhead' => 'Kop surat tenant (system marker)', 'qr' => 'QR verifikasi surat (system marker)',
            'tte' => 'TTE / QR verifikasi (system marker)',
        ];
    }

    /** @return list<string> */
    public function normalizeVariables(string $input): array
    {
        $variables = [];
        foreach (preg_split('/\R/', trim($input)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (LetterVariableSchema::isRepeater($line)) {
                $variables[] = $line;
                continue;
            }
            foreach (preg_split('/[\s,;]+/', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
                $variable = preg_replace('/^\{\{\s*|\s*\}\}$/', '', trim($token)) ?? trim($token);
                if (preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $variable)) $variables[] = $variable;
            }
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
        $text = $this->visibleTextFromXml($xml);
        $variables = [];

        preg_match_all('/\{\{#\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}(.*?)\{\{\/\s*\1\s*\}\}/s', $text, $blocks, PREG_SET_ORDER);
        foreach ($blocks as $block) {
            preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/', $block[2], $fields);
            $fields = array_values(array_unique($fields[1] ?? []));
            if ($fields) $variables[] = '@repeat ' . $block[1] . '|' . implode(',', $fields);
        }

        $plainText = preg_replace('/\{\{#\s*[A-Za-z_][A-Za-z0-9_]*\s*\}\}.*?\{\{\/\s*[A-Za-z_][A-Za-z0-9_]*\s*\}\}/s', '', $text) ?? $text;
        preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $plainText, $matches);

        return array_values(array_unique([...$variables, ...($matches[1] ?? [])]));
    }

    /** @param list<string> $declared @param list<string> $found */
    public function compareVariables(array $declared, array $found): array
    {
        $reserved = ['letterhead', 'qr', 'tte'];
        $declared = array_values(array_unique([...$declared, ...$reserved]));
        $found = array_values(array_unique($found));
        $declaredRepeaters = [];
        $foundRepeaters = [];

        foreach ($declared as $value) if (is_string($value) && ($r = LetterVariableSchema::parseRepeater($value))) $declaredRepeaters[$r['key']] = $r;
        foreach ($found as $value) if (is_string($value) && ($r = LetterVariableSchema::parseRepeater($value))) $foundRepeaters[$r['key']] = $r;

        $missing = array_values(array_diff($declared, $found, $reserved));
        $unknown = array_values(array_diff($found, $declared, $reserved));

        foreach ($declaredRepeaters as $key => $definition) {
            $foundDefinition = $foundRepeaters[$key] ?? null;
            if (! $foundDefinition) continue;
            $declaredFields = array_column($definition['fields'], 'key');
            $foundFields = array_column($foundDefinition['fields'], 'key');
            if ($declaredFields === $foundFields) continue;

            $missing = array_values(array_diff($missing, [array_search($definition['key'], $missing, true)]));
            $unknown = array_values(array_diff($unknown, [array_search($foundDefinition['key'], $unknown, true)]));
            if ($difference = array_diff($declaredFields, $foundFields)) $missing[] = '@repeat ' . $key . '|' . implode(',', $difference);
            if ($difference = array_diff($foundFields, $declaredFields)) $unknown[] = '@repeat ' . $key . '|' . implode(',', $difference);
        }

        return [
            'missing' => array_values(array_filter($missing, static fn ($value) => $value !== false)),
            'unknown' => array_values(array_filter($unknown, static fn ($value) => $value !== false)),
        ];
    }

    /** @param list<string> $found @param list<string> $allowed */
    public function validateVariables(array $found, array $allowed): array
    {
        $reserved = ['letterhead', 'qr', 'tte'];
        $allowed = array_values(array_unique([...$allowed, ...$reserved]));
        return [
            'missing' => array_values(array_diff($allowed, $found, $reserved)),
            'unknown' => array_values(array_diff($found, $allowed)),
        ];
    }

    private function visibleTextFromXml(string $xml): string
    {
        $dom = new DOMDocument();
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) return '';
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', DocxRendererService::WORD_NS);
        $nodes = $xpath->query('//w:t');
        $text = '';
        if ($nodes) foreach ($nodes as $node) $text .= $node->textContent . ' ';
        return trim($text);
    }
}
