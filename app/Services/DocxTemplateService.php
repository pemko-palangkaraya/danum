<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class DocxTemplateService
{
    /** @return array<string,string> */
    public function allowedVariables(): array
    {
        return [
            'number' => 'Nomor surat',
            'recipient_name' => 'Nama penerima',
            'recipient_address' => 'Alamat penerima',
            'recipient_birth_place' => 'Tempat lahir',
            'recipient_birth_date' => 'Tanggal lahir',
            'recipient_nik' => 'NIK',
            'subject' => 'Perihal / keperluan',
            'tenant_name' => 'Nama instansi / tenant',
            'tenant_city' => 'Kota / wilayah tenant',
            'tenant_district' => 'Kecamatan tenant',
            'tenant_village' => 'Kelurahan / desa tenant',
            'tenant_province' => 'Provinsi tenant',
            'tenant_address' => 'Alamat tenant',
            'tenant_phone' => 'Telepon tenant',
            'tenant_email' => 'Email tenant',
            'tenant_head_name' => 'Nama pejabat penandatangan',
            'tenant_head_title' => 'Jabatan pejabat penandatangan',
            'date' => 'Tanggal surat',
            'tte' => 'TTE / QR verifikasi (system marker)',
        ];
    }

    /** @return list<string> */
    public function normalizeVariables(string $input): array
    {
        $tokens = preg_split('/[\s,;]+/', trim($input), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $variables = [];
        foreach ($tokens as $token) {
            $variable = preg_replace('/^\{\{\s*|\s*\}\}$/', '', trim($token)) ?? trim($token);
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
        if ($zip->open($path) !== true) throw new RuntimeException('File DOCX tidak dapat dibuka.');
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        // Word frequently splits {{variable}} into several <w:t> runs when
        // the placeholder is typed/pasted or formatted. Read the visible text
        // across runs before looking for placeholders.
        $text = $this->visibleTextFromXml($xml);
        preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $text, $matches);
        return array_values(array_unique($matches[1] ?? []));
    }

    /** @param list<string> $declared @param list<string> $found */
    public function compareVariables(array $declared, array $found): array
    {
        // tte is a reserved system marker. It must be present in the DOCX to
        // define its position, but Super Admin does not need to register it
        // manually in the variable list.
        $reserved = ['tte'];
        $declared = array_values(array_unique([...$declared, ...$reserved]));
        $found = array_values(array_unique($found));

        return [
            'missing' => array_values(array_diff($declared, $found, $reserved)),
            'unknown' => array_values(array_diff($found, $declared, $reserved)),
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
            'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village,
            'tenant_province' => $tenant->province,
            'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone,
            'tenant_email' => $tenant->email,
            'tenant_head_name' => (string) ($tenant->head_name ?? ''),
            'tenant_head_title' => (string) ($tenant->head_title ?? ''),
            ...$data,
        ];

        // Replace placeholders across Word's <w:t> runs instead of applying
        // a regex directly to the raw XML. This keeps DOCX formatting intact
        // when Word has split {{variable}} over multiple runs.
        $xml = $this->replacePlaceholdersInXml($xml, $values);

        $tmp = tempnam(sys_get_temp_dir(), 'danum-docx-');
        if ($tmp === false || !copy($templatePath, $tmp)) throw new RuntimeException('Tidak dapat membuat DOCX hasil.');
        $output = new ZipArchive();
        if ($output->open($tmp) !== true) { @unlink($tmp); throw new RuntimeException('Tidak dapat membuka DOCX hasil.'); }
        $output->addFromString('word/document.xml', $xml);
        $output->close();

        $path = 'outgoing-letters/' . date('Y/m') . '/' . uniqid('letter-', true) . '.docx';
        Storage::disk('local')->put($path, file_get_contents($tmp));
        @unlink($tmp);
        return $path;
    }

    private function visibleTextFromXml(string $xml): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $nodes = $xpath->query('//w:t');
        $text = '';
        if ($nodes) {
            foreach ($nodes as $node) $text .= $node->textContent;
        }
        return $text;
    }

    /** @param array<string,mixed> $values */
    private function replacePlaceholdersInXml(string $xml, array $values): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            throw new RuntimeException('DOCX document.xml tidak valid.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $nodes = [];
        $nodeList = $xpath->query('//w:t');
        if ($nodeList) foreach ($nodeList as $node) $nodes[] = $node;

        // Work paragraph-by-paragraph so a placeholder cannot accidentally
        // consume text from two unrelated paragraphs.
        $paragraphs = $xpath->query('//w:p');
        if ($paragraphs) {
            foreach ($paragraphs as $paragraph) {
                $textNodes = [];
                $list = $xpath->query('.//w:t', $paragraph);
                if ($list) foreach ($list as $node) $textNodes[] = $node;
                if (!$textNodes) continue;

                $text = '';
                $offsets = [];
                foreach ($textNodes as $index => $node) {
                    $start = strlen($text);
                    $part = $node->textContent;
                    $text .= $part;
                    $offsets[] = [$start, strlen($text), $index];
                }

                preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $text, $matches, PREG_OFFSET_CAPTURE);
                if (empty($matches[0])) continue;

                // Process from right to left so offsets remain stable.
                for ($matchIndex = count($matches[0]) - 1; $matchIndex >= 0; $matchIndex--) {
                    $raw = $matches[0][$matchIndex][0];
                    $start = $matches[0][$matchIndex][1];
                    $length = strlen($raw);
                    $end = $start + $length;
                    $variable = $matches[1][$matchIndex][0];

                    $first = null;
                    $last = null;
                    foreach ($offsets as [$nodeStart, $nodeEnd, $index]) {
                        if ($nodeEnd > $start && $nodeStart < $end) {
                            $first ??= $index;
                            $last = $index;
                        }
                    }
                    if ($first === null || $last === null) continue;

                    $replacement = $variable === 'tte'
                        ? '{{tte}}'
                        : htmlspecialchars((string) ($values[$variable] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');

                    $firstNode = $textNodes[$first];
                    $firstText = $firstNode->textContent;
                    $firstStart = $offsets[$first][0];
                    $localStart = max(0, $start - $firstStart);
                    $prefix = substr($firstText, 0, $localStart);

                    $lastText = $textNodes[$last]->textContent;
                    $lastStart = $offsets[$last][0];
                    $localEnd = min(strlen($lastText), $end - $lastStart);
                    $suffix = substr($lastText, $localEnd);

                    $firstNode->nodeValue = $prefix . $replacement . ($first === $last ? $suffix : '');
                    for ($i = $first + 1; $i <= $last; $i++) $textNodes[$i]->nodeValue = ($i === $last ? $suffix : '');
                }
            }
        }

        return $dom->saveXML() ?: $xml;
    }

    public function extractText(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('File DOCX tidak dapat dibuka.');
        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if ($dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $paragraphs = $xpath->query('//w:p');
            $parts = [];
            if ($paragraphs) foreach ($paragraphs as $paragraph) {
                $nodes = $xpath->query('.//w:t', $paragraph);
                $line = '';
                if ($nodes) foreach ($nodes as $node) $line .= $node->textContent;
                if ($line !== '') $parts[] = $line;
            }
            return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $parts)) ?? implode("\n", $parts));
        }
        return trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }
}
