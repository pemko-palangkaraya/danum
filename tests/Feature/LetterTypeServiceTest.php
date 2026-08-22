<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Services\LetterTypeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypeServiceTest extends TestCase
{
    use RefreshDatabase;

    private LetterTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LetterTypeService::class);
    }

    public function test_can_find_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $result = $this->service->find($letterType->id, $letterType->tenant_id);

        $this->assertInstanceOf(LetterType::class, $result);
        $this->assertSame($letterType->id, $result->id);
    }

    public function test_can_get_all_letter_types(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();

        LetterType::factory()->count(3)->create(['tenant_id' => $tenant->id]);
        LetterType::factory()->create();

        $result = $this->service->getAll($tenant->id);

        $this->assertCount(3, $result);
    }

    public function test_can_create_letter_type(): void
    {
        $data = LetterType::factory()->make()->toArray();

        $letterType = $this->service->create($data);

        $this->assertInstanceOf(LetterType::class, $letterType);

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
        ]);
    }

    public function test_creating_letter_type_with_template_creates_version_one(): void
    {
        $letterType = $this->service->create([
            ...LetterType::factory()->make()->toArray(),
            'body_template' => 'Nomor {{number}} untuk {{recipient_name}}.',
        ]);

        $this->assertDatabaseHas('letter_type_versions', [
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'body_template' => 'Nomor {{number}} untuk {{recipient_name}}.',
        ]);
    }

    public function test_changing_template_creates_a_new_immutable_version(): void
    {
        $letterType = $this->service->create([
            ...LetterType::factory()->make()->toArray(),
            'body_template' => 'Versi {{number}}.',
        ]);

        $this->service->update($letterType, [
            'body_template' => 'Versi baru {{number}} untuk {{recipient_name}}.',
        ]);

        $this->assertDatabaseHas('letter_type_versions', [
            'letter_type_id' => $letterType->id,
            'version' => 1,
            'body_template' => 'Versi {{number}}.',
        ]);

        $this->assertDatabaseHas('letter_type_versions', [
            'letter_type_id' => $letterType->id,
            'version' => 2,
            'body_template' => 'Versi baru {{number}} untuk {{recipient_name}}.',
        ]);
    }

    public function test_same_template_update_does_not_create_duplicate_version(): void
    {
        $template = 'Nomor {{number}}.';
        $letterType = $this->service->create([
            ...LetterType::factory()->make()->toArray(),
            'body_template' => $template,
        ]);

        $this->service->update($letterType, ['body_template' => $template]);

        $this->assertSame(1, LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->count());
    }

    public function test_can_update_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $updated = $this->service->update($letterType, [
            'name' => 'Updated Letter Type',
        ]);

        $this->assertSame('Updated Letter Type', $updated->name);
    }

    public function test_can_delete_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $result = $this->service->delete($letterType);

        $this->assertTrue($result);

        $this->assertSoftDeleted('letter_types', [
            'id' => $letterType->id,
        ]);
    }

    public function test_can_restore_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $letterType->delete();

        $result = $this->service->restore($letterType);

        $this->assertTrue($result);

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'deleted_at' => null,
        ]);
    }
}
