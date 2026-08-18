<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LetterType;
use App\Repositories\LetterTypeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LetterTypeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private LetterTypeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new LetterTypeRepository();
    }

    public function test_can_create_letter_type(): void
    {
        $letterType = $this->repository->create(
            LetterType::factory()->make()->toArray()
        );

        $this->assertInstanceOf(LetterType::class, $letterType);

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
        ]);
    }

    public function test_can_find_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $result = $this->repository->find($letterType->id);

        $this->assertInstanceOf(LetterType::class, $result);
        $this->assertSame($letterType->id, $result->id);
    }

    public function test_can_get_all_letter_types(): void
    {
        LetterType::factory()->count(3)->create();

        $result = $this->repository->getAll();

        $this->assertCount(3, $result);
    }

    public function test_can_update_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $updated = $this->repository->update($letterType, [
            'name' => 'Updated Letter Type',
            'description' => 'Updated description',
        ]);

        $this->assertSame('Updated Letter Type', $updated->name);
        $this->assertSame(
            'Updated description',
            $updated->description
        );
    }

    public function test_can_delete_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $result = $this->repository->delete($letterType);

        $this->assertTrue($result);

        $this->assertSoftDeleted('letter_types', [
            'id' => $letterType->id,
        ]);
    }

    public function test_can_restore_letter_type(): void
    {
        $letterType = LetterType::factory()->create();

        $letterType->delete();

        $result = $this->repository->restore($letterType);

        $this->assertTrue($result);

        $this->assertDatabaseHas('letter_types', [
            'id' => $letterType->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_find_letter_type_with_trashed(): void
    {
        $letterType = LetterType::factory()->create();

        $letterType->delete();

        $result = $this->repository->findWithTrashed($letterType->id);

        $this->assertInstanceOf(LetterType::class, $result);
        $this->assertSame($letterType->id, $result->id);
    }
}
