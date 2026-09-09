<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Models\LetterType;
use App\Models\LetterVariableDefinition;
use App\Services\LetterVariableDefinitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterVariableCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_reports_only_undefined_non_system_variables(): void
    {
        LetterVariableDefinition::query()->create([
            'key' => 'nama_saksi',
            'label' => 'Nama Saksi',
            'type' => 'text',
            'source' => 'manual',
            'required' => true,
            'readonly' => false,
            'is_active' => true,
        ]);

        $undefined = app(LetterVariableDefinitionService::class)->undefined([
            'nama_saksi',
            'tenant_name',
            '@repeat children|Nama:nama',
            'belum_terdaftar',
        ]);

        $this->assertSame(['belum_terdaftar'], $undefined);
    }

    public function test_version_creation_rejects_variable_missing_from_catalog(): void
    {
        $letterType = LetterType::factory()->create([
            'tenant_id' => null,
            'body_template' => 'body-v1',
            'template_path' => 'letter-templates/v1.docx',
            'variables' => ['belum_terdaftar'],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Katalog Variabel');

        app(\App\Services\LetterTypeService::class)->ensureCurrentVersion($letterType);
    }
}
