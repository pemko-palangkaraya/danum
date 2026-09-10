<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LetterFont;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class DocxTemplateService
{
    public function __construct(
        private readonly DocxVariableService $variableService,
        private readonly DocxRendererService $renderer,
        private readonly DocxLetterheadService $letterhead,
        private readonly DocxFontService $fonts,
    ) {}

    /** @return array<string,string> */
    public function allowedVariables(): array { return $this->variableService->allowedVariables(); }
    /** @return list<string> */
    public function normalizeVariables(string $input): array { return $this->variableService->normalizeVariables($input); }
    /** @return list<string> */
    public function extractVariables(string $path): array { return $this->variableService->extractVariables($path); }
    /** @param list<string> $declared @param list<string> $found */
    public function compareVariables(array $declared, array $found): array { return $this->variableService->compareVariables($declared, $found); }
    /** @param list<string> $found @param list<string> $allowed */
    public function validateVariables(array $found, array $allowed): array { return $this->variableService->validateVariables($found, $allowed); }

    public function validate(string $template): void
    {
        preg_match_all('/\{\{\s*([A-Za-z_][A-Za-z0-9_.]*)\s*\}\}/', $template, $matches);
        $allowed = array_keys($this->allowedVariables());
        $reserved = ['letterhead', 'qr', 'tte'];
        foreach (array_unique($matches[1] ?? []) as $variable) {
            if (! in_array($variable, [...$allowed, ...$reserved], true)) throw new \InvalidArgumentException(sprintf('Unknown letter template variable: %s.', $variable));
        }
    }

    /** @param array<string,mixed> $data */
    public function renderToStorage(string $templatePath, Tenant $tenant, array $data, LetterFont $font = LetterFont::ARIAL): string
    {
        [$xml, $rels, $contentTypes] = $this->readTemplate($templatePath);
        $values = $this->tenantValues($tenant, $data);
        $xml = $this->renderer->render($xml, $values);
        $xml = $this->fonts->apply($xml, $font);
        $letterhead = $this->letterhead->embed($xml, $rels, $contentTypes, $tenant);
        if ($letterhead !== null) {
            $xml = $letterhead['xml'];
            $rels = $letterhead['rels'];
            $contentTypes = $letterhead['contentTypes'];
        }

        $tmp = tempnam(sys_get_temp_dir(), 'danum-docx-');
        if ($tmp === false || ! copy($templatePath, $tmp)) throw new RuntimeException('Tidak dapat membuat DOCX hasil.');
        $output = new ZipArchive();
        if ($output->open($tmp) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Tidak dapat membuka DOCX hasil.');
        }
        $output->addFromString('word/document.xml', $xml);
        if ($letterhead !== null && $letterhead['mediaName'] !== '' && $letterhead['mediaPath'] !== '') {
            $output->addFile($letterhead['mediaPath'], 'word/media/' . $letterhead['mediaName']);
            $output->addFromString('word/_rels/document.xml.rels', $rels);
            $output->addFromString('[Content_Types].xml', $contentTypes);
        } elseif ($letterhead !== null) {
            $output->addFromString('word/_rels/document.xml.rels', $rels);
            $output->addFromString('[Content_Types].xml', $contentTypes);
        }
        $output->close();

        $path = 'outgoing-letters/' . date('Y/m') . '/' . uniqid('letter-', true) . '.docx';
        $contents = file_get_contents($tmp);
        @unlink($tmp);
        if ($contents === false || ! Storage::disk('local')->put($path, $contents)) throw new RuntimeException('Tidak dapat menyimpan DOCX hasil.');
        return $path;
    }

    /** @return array{0:string,1:string,2:string} */
    private function readTemplate(string $templatePath): array
    {
        $source = new ZipArchive();
        if ($source->open($templatePath) !== true) throw new RuntimeException('File DOCX template tidak dapat dibuka.');
        $xml = $source->getFromName('word/document.xml');
        $rels = $source->getFromName('word/_rels/document.xml.rels') ?: $this->emptyRelationships();
        $contentTypes = $source->getFromName('[Content_Types].xml') ?: '';
        $source->close();
        if ($xml === false) throw new RuntimeException('DOCX tidak memiliki word/document.xml.');
        return [$xml, $rels, $contentTypes];
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function tenantValues(Tenant $tenant, array $data): array
    {
        return [
            'tenant_name' => $tenant->name, 'tenant_city' => $tenant->city, 'tenant_district' => $tenant->district,
            'tenant_village' => $tenant->village, 'tenant_province' => $tenant->province, 'tenant_address' => $tenant->address,
            'tenant_phone' => $tenant->phone, 'tenant_email' => $tenant->email,
            'tenant_head_name' => (string) ($tenant->head_name ?? ''), 'tenant_head_title' => (string) ($tenant->head_title ?? ''),
            'tenant_head_nip' => (string) ($tenant->head_nip ?? ''), 'nama_ttd' => (string) ($tenant->head_name ?? ''),
            'jabatan_ttd' => (string) ($tenant->head_title ?? ''), 'nip_ttd' => (string) ($tenant->head_nip ?? ''), ...$data,
        ];
    }

    private function emptyRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>';
    }
}
