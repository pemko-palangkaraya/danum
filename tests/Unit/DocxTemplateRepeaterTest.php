<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant;
use App\Services\DocxTemplateService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class DocxTemplateRepeaterTest extends TestCase
{
    public function test_docx_table_row_is_repeated_for_each_item(): void
    {
        Storage::fake('local');
        $template = tempnam(sys_get_temp_dir(), 'danum-template-') . '.docx';
        $output = null;

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($template, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:tbl><w:tr><w:tc><w:p><w:r><w:t>{{#pelaksana}}Nama: {{nama}}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>NIP: {{nip}}{{/pelaksana}}</w:t></w:r></w:p></w:tc></w:tr></w:tbl></w:body></w:document>');
        $zip->close();

        try {
            $tenant = new Tenant(['name' => 'Demo Tenant']);
            $output = app(DocxTemplateService::class)->renderToStorage($template, $tenant, [
                'pelaksana' => [
                    ['nama' => 'Budi Santoso', 'nip' => '19800001'],
                    ['nama' => 'Ahmad Fauzi', 'nip' => '19800002'],
                ],
            ]);

            $bytes = Storage::disk('local')->get($output);
            $result = tempnam(sys_get_temp_dir(), 'danum-result-') . '.docx';
            file_put_contents($result, $bytes);
            $read = new ZipArchive();
            $this->assertTrue($read->open($result));
            $xml = $read->getFromName('word/document.xml') ?: '';
            $read->close();

            $this->assertStringContainsString('Budi Santoso', $xml);
            $this->assertStringContainsString('19800001', $xml);
            $this->assertStringContainsString('Ahmad Fauzi', $xml);
            $this->assertStringContainsString('19800002', $xml);
            $this->assertStringNotContainsString('{{#pelaksana}}', $xml);
            $this->assertStringNotContainsString('{{/pelaksana}}', $xml);
            @unlink($result);
        } finally {
            @unlink($template);
            if ($output) Storage::disk('local')->delete($output);
        }
    }
}
