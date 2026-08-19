<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutgoingLetter;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OutgoingLetterService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
    ) {}

    public function getAll(string $tenantId): Collection
    {
        return $this->repository->getAll($tenantId);
    }

    public function find(string $id, string $tenantId): ?OutgoingLetter
    {
        return $this->repository->find($id, $tenantId);
    }

    public function findWithTrashed(string $id, string $tenantId): ?OutgoingLetter
    {
        return $this->repository->findWithTrashed($id, $tenantId);
    }

    public function create(array $data): OutgoingLetter
    {
        return $this->repository->create($data);
    }

    public function update(OutgoingLetter $outgoingLetter, array $data): OutgoingLetter
    {
        return $this->repository->update($outgoingLetter, $data);
    }

    public function delete(OutgoingLetter $outgoingLetter): bool
    {
        return $this->repository->delete($outgoingLetter);
    }

    public function restore(OutgoingLetter $outgoingLetter): bool
    {
        return $this->repository->restore($outgoingLetter);
    }
}
