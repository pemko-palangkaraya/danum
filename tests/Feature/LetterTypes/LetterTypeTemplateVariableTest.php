<?php

declare(strict_types=1);

namespace Tests\Feature\LetterTypes;

use App\Models\LetterType;
use App\Models\Tenant;
use App\Services\OutgoingLetterTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LetterTypeTemplateVariableTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_supported_variables_are_rendered(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'DANUM', 'city' => 'Palangka Raya', 'head_name' => 'Kepala DANUM']);
        $type = LetterType::factory()->create();
        $service = app(OutgoingLetterTemplateService::class);

        $template = '{{number}}|{{recipient_name}}|{{recipient_address}}|{{subject}}|{{tenant_name}}|{{tenant_city}}|{{tenant_head_name}}';
        $result = $service->render($type->forceFill(['body_template' => $template]), $tenant, [
            'number' => '001/SK/2026',
            'recipient_name' => 'Budi',
            'recipient_address' => 'Jl. Merdeka',
            'subject' => 'Undangan',
        ]);

        $this->assertSame('001/SK/2026|Budi|Jl. Merdeka|Undangan|DANUM|Palangka Raya|Kepala DANUM', $result);
    }

    public function test_unknown_template_variable_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(OutgoingLetterTemplateService::class)->validate('Halo {{unknown_variable}}');
    }
}
