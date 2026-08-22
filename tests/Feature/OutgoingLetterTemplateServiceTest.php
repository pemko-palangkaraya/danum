<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Services\OutgoingLetterTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OutgoingLetterTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_supported_variables_are_rendered(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Kelurahan Danum',
            'city' => 'Palangka Raya',
            'head_name' => 'Siti Rahma',
        ]);
        $letterType = LetterType::factory()->create([
            'tenant_id' => $tenant->id,
            'body_template' => '{{number}} - {{recipient_name}} - {{tenant_head_name}}',
        ]);

        $content = app(OutgoingLetterTemplateService::class)->render(
            $letterType,
            $tenant,
            ['number' => '001/SK/2026', 'recipient_name' => 'Budi'],
        );

        $this->assertSame('001/SK/2026 - Budi - Siti Rahma', $content);
    }

    public function test_unknown_variables_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown letter template variable: unknown.');

        app(OutgoingLetterTemplateService::class)->validate('Nomor {{unknown}}');
    }

    public function test_variable_registry_is_explicit(): void
    {
        $variables = app(OutgoingLetterTemplateService::class)->variables();

        $this->assertArrayHasKey('number', $variables);
        $this->assertArrayHasKey('recipient_name', $variables);
        $this->assertArrayHasKey('tenant_name', $variables);
        $this->assertArrayHasKey('tenant_head_name', $variables);
    }
}
