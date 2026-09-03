<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Support\LetterVariableSchema;
use DOMDocument;
use DOMXPath;
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
            'recipient_name' => 'Nama pemohon',
            'recipient_address' => 'Alamat pemohon',
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
            'letterhead' => 'Kop surat tenant (system marker)',
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
        $reserved = ['letterhead', 'tte'];
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
            if ($declaredFields !== $foundFields) {
                $missing = array_values(array_diff($missing, [array_search($definition['key'], $missing, true)]));
                $unknown = array_values(array_diff($unknown, [array_search($foundDefinition['key'], $unknown, true)]));
                if (array_diff($declaredFields, $foundFields)) $missing[] = '@repeat ' . $key . '|' . implode(',', array_diff($declaredFields, $foundFields));
                if (array_diff($foundFields, $declaredFields)) $unknown[] = '@repeat ' . $key . '|' . implode(',', array_diff($foundFields, $declaredFields));
            }
        }
        return ['missing' => array_values(array_filter($missing, static fn($v) => $v !== false)), 'unknown' => array_values(array_filter($unknown, static fn($v) => $v !== false))];
    }

    /** @param list<string> $found @param list<string> $allowed */
    public function validateVariables(array $found, array $allowed): array
    {
        $allowed = array_values(array_unique([...$allowed, 'letterhead', 'tte']));
        return [
            'missing' => array_values(array_diff($allowed, $found, ['letterhead', 'tte'])),
            'unknown' => array_values(array_diff($found, $allowed)),
        ];
    }

    /** @param array<string,mixed> $data */
    public function renderToStorage(string $templatePath, Tenant $tenant, array $data): string
    {
        $source = new ZipArchive();
        if ($source->open($templatePath) !== true) throw new RuntimeException('File DOCX template tidak dapat dibuka.');
        $xml = $source->getFromName('word/document.xml');
        $rels = $source->getFromName('word/_rels/document.xml.rels') ?: $this->emptyRelationships();
        $contentTypes = $source->getFromName('[Content_Types].xml') ?: '';
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

        $xml = $this->renderRepeatersInXml($xml, $values);
        $xml = $this->replacePlaceholdersInXml($xml, $values);

        $letterheadPath = $this->resolveLetterheadPath($tenant);
        if ($letterheadPath !== null) [$xml, $rels, $contentTypes, $mediaName] = $this->embedLetterhead($xml, $rels, $contentTypes, $letterheadPath);

        $tmp = tempnam(sys_get_temp_dir(), 'danum-docx-');
        if ($tmp === false || !copy($templatePath, $tmp)) throw new RuntimeException('Tidak dapat membuat DOCX hasil.');
        $output = new ZipArchive();
        if ($output->open($tmp) !== true) { @unlink($tmp); throw new RuntimeException('Tidak dapat membuka DOCX hasil.'); }
        $output->addFromString('word/document.xml', $xml);
        if ($letterheadPath !== null && isset($mediaName)) {
            $output->addFile($letterheadPath, 'word/media/' . $mediaName);
            $output->addFromString('word/_rels/document.xml.rels', $rels);
            $output->addFromString('[Content_Types].xml', $contentTypes);
        }
        $output->close();
        $path = 'outgoing-letters/' . date('Y/m') . '/' . uniqid('letter-', true) . '.docx';
        Storage::disk('local')->put($path, file_get_contents($tmp));
        @unlink($tmp);
        return $path;
    }

    /** @param array<string,mixed> $values */
    private function renderRepeatersInXml(string $xml, array $values): string
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX document.xml tidak valid.');
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $rows = $xpath->query('//w:tr');
        if ($rows) foreach (iterator_to_array($rows) as $row) {
            $text = $this->nodeText($xpath, $row);
            if (! preg_match('/\{\{#\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}(.*?)\{\{\/\s*\1\s*\}\}/s', $text, $match)) continue;
            $items = $values[$match[1]] ?? [];
            if (! is_array($items)) $items = [];
            $parent = $row->parentNode;
            if (! $parent) continue;
            foreach ($items as $item) {
                if (! is_array($item)) continue;
                $clone = $row->cloneNode(true);
                $this->replaceRepeatMarkersInNode($xpath, $clone, $match[1], $item);
                $parent->insertBefore($clone, $row);
            }
            $parent->removeChild($row);
        }

        $paragraphs = $xpath->query('//w:p');
        if ($paragraphs) foreach (iterator_to_array($paragraphs) as $paragraph) {
            $text = $this->nodeText($xpath, $paragraph);
            if (! preg_match('/^\s*\{\{#\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}\s*$/', $text, $start)) continue;
            $key = $start[1];
            $end = $paragraph->nextSibling;
            $block = [];
            while ($end) {
                if (preg_match('/^\s*\{\{\/\s*' . preg_quote($key, '/') . '\s*\}\}\s*$/', $this->nodeText($xpath, $end))) break;
                $block[] = $end;
                $end = $end->nextSibling;
            }
            if (! $end) continue;
            $items = $values[$key] ?? [];
            if (! is_array($items)) $items = [];
            $container = $paragraph->parentNode;
            if (! $container) continue;
            foreach ($items as $item) {
                if (! is_array($item)) continue;
                foreach ($block as $sourceNode) {
                    $clone = $sourceNode->cloneNode(true);
                    $this->replaceRepeatMarkersInNode($xpath, $clone, $key, $item);
                    $container->insertBefore($clone, $end);
                }
            }
            $container->removeChild($paragraph);
            foreach ($block as $sourceNode) $container->removeChild($sourceNode);
            $container->removeChild($end);
        }
        return $dom->saveXML() ?: $xml;
    }

    private function nodeText(DOMXPath $xpath, \DOMNode $node): string
    {
        $nodes = $xpath->query('.//w:t', $node);
        $text = '';
        if ($nodes) foreach ($nodes as $textNode) $text .= $textNode->textContent;
        return $text;
    }

    /** @param array<string,mixed> $item */
    private function replaceRepeatMarkersInNode(DOMXPath $xpath, \DOMNode $node, string $key, array $item): void
    {
        $nodes = $xpath->query('.//w:t', $node);
        if (! $nodes) return;
        foreach ($nodes as $textNode) {
            $value = preg_replace_callback('/\{\{#\s*' . preg_quote($key, '/') . '\s*\}\}|\{\{\/\s*' . preg_quote($key, '/') . '\s*\}\}|\{\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}\}/', static function ($match) use ($item) {
                if (isset($match[1]) && $match[1] !== '') return htmlspecialchars((string) ($item[$match[1]] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                return '';
            }, $textNode->textContent);
            $textNode->nodeValue = $value ?? $textNode->textContent;
        }
    }

    private function resolveLetterheadPath(Tenant $tenant): ?string
    {
        if (! $tenant->letterhead_path) return null;
        foreach (['public', 'local'] as $diskName) {
            $disk = Storage::disk($diskName);
            if ($disk->exists($tenant->letterhead_path)) return $disk->path($tenant->letterhead_path);
        }
        return null;
    }

    private function embedLetterhead(string $xml, string $rels, string $contentTypes, string $imagePath): array
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $allowed = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif'];
        if (! isset($allowed[$extension])) throw new RuntimeException('Kop surat harus berupa PNG, JPG, JPEG, atau GIF.');
        $mime = $allowed[$extension];
        $mediaName = 'danum-letterhead-' . substr(sha1($imagePath), 0, 12) . '.' . $extension;
        $rid = 'rIdDanumLetterhead';
        $dom = new DOMDocument(); $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX document.xml tidak valid.');
        $xpath = new DOMXPath($dom); $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $nodes = $xpath->query('//w:t[contains(., "{{letterhead}}")]');
        if (! $nodes || $nodes->length === 0) return [$xml, $rels, $contentTypes, $mediaName];
        $size = @getimagesize($imagePath); $width = (int) ($size[0] ?? 1200); $height = (int) ($size[1] ?? 300); $cx = 6500000; $cy = max(1, (int) round($height * ($cx / $width)));
        $drawingXml = '<w:r xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="' . $cx . '" cy="' . $cy . '"/><wp:docPr id="9001" name="DANUM Letterhead"/><a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic><pic:nvPicPr><pic:cNvPr id="0" name="' . $mediaName . '"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="' . $rid . '"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>';
        $fragment = $dom->createDocumentFragment(); $fragment->appendXML($drawingXml); $run = $nodes->item(0)->parentNode;
        if (! $run) return [$xml, $rels, $contentTypes, $mediaName];
        $run->parentNode?->replaceChild($fragment, $run);
        $relsDom = new DOMDocument(); $relsDom->preserveWhiteSpace = true; $relsDom->loadXML($rels, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING); $root = $relsDom->documentElement;
        $rel = $relsDom->createElementNS('http://schemas.openxmlformats.org/package/2006/relationships', 'Relationship'); $rel->setAttribute('Id', $rid); $rel->setAttribute('Type', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image'); $rel->setAttribute('Target', 'media/' . $mediaName); $root?->appendChild($rel); $rels = $relsDom->saveXML() ?: $rels;
        $ctDom = new DOMDocument(); $ctDom->preserveWhiteSpace = true; $ctDom->loadXML($contentTypes, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING); $ctRoot = $ctDom->documentElement; $default = $ctDom->createElementNS('http://schemas.openxmlformats.org/package/2006/content-types', 'Default'); $default->setAttribute('Extension', $extension); $default->setAttribute('ContentType', $mime); $exists = false; foreach ($ctDom->getElementsByTagName('Default') as $item) if (strcasecmp($item->getAttribute('Extension'), $extension) === 0) $exists = true; if (! $exists) $ctRoot?->appendChild($default); $contentTypes = $ctDom->saveXML() ?: $contentTypes;
        return [$dom->saveXML() ?: $xml, $rels, $contentTypes, $mediaName];
    }

    private function emptyRelationships(): string { return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>'; }

    private function visibleTextFromXml(string $xml): string
    {
        $dom = new DOMDocument(); $dom->preserveWhiteSpace = true; $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING); $xpath = new DOMXPath($dom); $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'); $nodes = $xpath->query('//w:t'); $text = ''; if ($nodes) foreach ($nodes as $node) $text .= $node->textContent; return $text;
    }

    /** @param array<string,mixed> $values */
    private function replacePlaceholdersInXml(string $xml, array $values): string
    {
        $dom = new DOMDocument(); $dom->preserveWhiteSpace = true; if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) throw new RuntimeException('DOCX document.xml tidak valid.');
        $xpath = new DOMXPath($dom); $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'); $paragraphs = $xpath->query('//w:p');
        if ($paragraphs) foreach ($paragraphs as $paragraph) {
            $textNodes = []; $list = $xpath->query('.//w:t', $paragraph); if ($list) foreach ($list as $node) $textNodes[] = $node; if (! $textNodes) continue;
            $text = ''; $offsets = []; foreach ($textNodes as $index => $node) { $start = strlen($text); $text .= $node->textContent; $offsets[] = [$start, strlen($text), $index]; }
            preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $text, $matches, PREG_OFFSET_CAPTURE); if (empty($matches[0])) continue;
            for ($matchIndex = count($matches[0]) - 1; $matchIndex >= 0; $matchIndex--) {
                $raw = $matches[0][$matchIndex][0]; $start = $matches[0][$matchIndex][1]; $end = $start + strlen($raw); $variable = $matches[1][$matchIndex][0]; $first = null; $last = null;
                foreach ($offsets as [$nodeStart, $nodeEnd, $index]) if ($nodeEnd > $start && $nodeStart < $end) { $first ??= $index; $last = $index; }
                if ($first === null || $last === null) continue;
                $replacement = htmlspecialchars((string) ($values[$variable] ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8'); $firstNode = $textNodes[$first]; $firstStart = $offsets[$first][0]; $localStart = max(0, $start - $firstStart); $prefix = substr($firstNode->textContent, 0, $localStart); $lastText = $textNodes[$last]->textContent; $lastStart = $offsets[$last][0]; $localEnd = min(strlen($lastText), $end - $lastStart); $suffix = substr($lastText, $localEnd); $firstNode->nodeValue = $prefix . $replacement . ($first === $last ? $suffix : ''); for ($i = $first + 1; $i <= $last; $i++) $textNodes[$i]->nodeValue = ($i === $last ? $suffix : '');
            }
        }
        return $dom->saveXML() ?: $xml;
    }

    public function extractText(string $path): string
    {
        $zip = new ZipArchive(); if ($zip->open($path) !== true) throw new RuntimeException('File DOCX tidak dapat dibuka.'); $xml = $zip->getFromName('word/document.xml') ?: ''; $zip->close(); $dom = new DOMDocument(); $dom->preserveWhiteSpace = true;
        if ($dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) { $xpath = new DOMXPath($dom); $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'); $paragraphs = $xpath->query('//w:p'); $parts = []; if ($paragraphs) foreach ($paragraphs as $paragraph) { $nodes = $xpath->query('.//w:t', $paragraph); $line = ''; if ($nodes) foreach ($nodes as $node) $line .= $node->textContent; if ($line !== '') $parts[] = $line; } return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $parts)) ?? implode("\n", $parts)); }
        return trim(html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8'));
    }
}
