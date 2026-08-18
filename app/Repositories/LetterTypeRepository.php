<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LetterType;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LetterTypeRepository implements LetterTypeRepositoryInterface
{
    public function find(string $id): ?LetterType
    {
        return LetterType::query()->find($id);
    }

    public function getAll(): Collection
    {
        return LetterType::query()
            ->get();
    }

    public function create(array $data): LetterType
    {
        return LetterType::query()->create($data);
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        $letterType->update($data);

        return $letterType->refresh();
    }

    public function delete(LetterType $letterType): bool
    {
        return $letterType->delete();
    }

    public function restore(LetterType $letterType): bool
    {
        return $letterType->restore();
    }

    public function findWithTrashed(string $id): ?LetterType
    {
        return LetterType::withTrashed()->find($id);
    }
}
