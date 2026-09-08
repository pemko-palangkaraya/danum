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

    public function test_signature_table_rows_are_protected_from_page_splitting(): void
    {
        Storage::fake('local');
        $template = tempnam(sys_get_temp_dir(), 'danum-signature-template-') . '.docx';
        $output = null;

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($template, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/></Types>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>Isi surat</w:t></w:r></w:p><w:tbl><w:tr><w:tc><w:p><w:r><w:t>Palangka Raya, {{date}}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{{qr}}</w:t></w:r></w:p></w:tc></w:tr><w:tr><w:tc><w:p><w:r><w:t>{{jabatan_ttd}}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>{{tte}}</w:t></w:r></w:p></w:tc></w:tr><w:tr><w:tc><w:p><w:r><w:t>{{nama_ttd}}</w:t></w:r></w:p><w:p><w:r><w:t>{{pangkat_ttd}}</w:t></w:r></w:p><w:p><w:r><w:t>{{golongan_ttd}}</w:t></w:r></w:p><w:p><w:r><w:t>NIP {{nip_ttd}}</w:t></w:r></w:p></w:tc><w:tc><w:p><w:r><w:t>Penandatangan</w:t></w:r></w:p></w:tc></w:tr></w:tbl></w:body></w:document>');
        $zip->close();

        try {
            $tenant = new Tenant(['name' => 'Demo Tenant']);
            $output = app(DocxTemplateService::class)->renderToStorage($template, $tenant, [
                'date' => '1 September 2026',
                'jabatan_ttd' => 'Lurah Mungku Baru',
                'nama_ttd' => 'Meysa Yudhistira, S.Kom.',
                'pangkat_ttd' => 'Pembina',
                'golongan_ttd' => 'IV/e',
                'nip_ttd' => '199105232025041001',
            ]);

            $bytes = Storage::disk('local')->get($output);
            $result = tempnam(sys_get_temp_dir(), 'danum-signature-result-') . '.docx';
            file_put_contents($result, $bytes);
            $read = new ZipArchive();
            $this->assertTrue($read->open($result));
            $xml = $read->getFromName('word/document.xml') ?: '';
            $read->close();

            $this->assertSame(3, substr_count($xml, '<w:cantSplit'));
            $this->assertSame(7, substr_count($xml, '<w:keepNext'));
            $this->assertStringContainsString('Meysa Yudhistira, S.Kom.', $xml);
            $this->assertStringContainsString('NIP 199105232025041001', $xml);
            @unlink($result);
        } finally {
            @unlink($template);
            if ($output) Storage::disk('local')->delete($output);
        }
    }
}
