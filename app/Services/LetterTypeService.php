<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LetterTypeService
{
    public function __construct(
        private readonly LetterTypeRepositoryInterface $repository,
    ) {}

    public function find(string $id): ?LetterType
    {
        return $this->repository->find($id);
    }

    public function findWithTrashed(string $id): ?LetterType
    {
        return $this->repository->findWithTrashed($id);
    }

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function create(array $data): LetterType
    {
        return $this->repository->create($data);
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        return $this->repository->update($letterType, $data);
    }

    public function delete(LetterType $letterType): bool
    {
        return $this->repository->delete($letterType);
    }

    public function restore(LetterType $letterType): bool
    {
        return $this->repository->restore($letterType);
    }
}
