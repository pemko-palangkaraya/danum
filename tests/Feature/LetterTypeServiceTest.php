<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterType;
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

        $result = $this->service->find($letterType->id);

        $this->assertInstanceOf(LetterType::class, $result);
        $this->assertSame($letterType->id, $result->id);
    }

    public function test_can_get_all_letter_types(): void
    {
        LetterType::factory()->count(3)->create();

        $result = $this->service->getAll();

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
