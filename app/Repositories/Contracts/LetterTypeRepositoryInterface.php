<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\LetterType;
use Illuminate\Database\Eloquent\Collection;

interface LetterTypeRepositoryInterface
{
    public function find(string $id): ?LetterType;

    public function getAll(): Collection;

    public function create(array $data): LetterType;

    public function update(LetterType $letterType, array $data): LetterType;

    public function delete(LetterType $letterType): bool;

    public function restore(LetterType $letterType): bool;

    public function findWithTrashed(string $id): ?LetterType;
}
