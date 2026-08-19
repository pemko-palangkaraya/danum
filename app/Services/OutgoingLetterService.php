<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
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

    public function validate(OutgoingLetter $outgoingLetter): OutgoingLetter
    {
        return $this->transition(
            $outgoingLetter,
            OutgoingLetterStatus::DRAFT,
            OutgoingLetterStatus::VALIDATED,
        );
    }

    public function issue(OutgoingLetter $outgoingLetter): OutgoingLetter
    {
        return $this->transition(
            $outgoingLetter,
            OutgoingLetterStatus::VALIDATED,
            OutgoingLetterStatus::ISSUED,
            ['issued_at' => now()->toDateString()],
        );
    }

    public function cancel(OutgoingLetter $outgoingLetter): OutgoingLetter
    {
        if (! in_array(
            $outgoingLetter->status,
            [OutgoingLetterStatus::DRAFT, OutgoingLetterStatus::VALIDATED],
            true,
        )) {
            throw new \DomainException('Only draft or validated letters can be cancelled.');
        }

        return $this->repository->update($outgoingLetter, [
            'status' => OutgoingLetterStatus::CANCELLED,
        ]);
    }

    private function transition(
        OutgoingLetter $outgoingLetter,
        OutgoingLetterStatus $from,
        OutgoingLetterStatus $to,
        array $attributes = [],
    ): OutgoingLetter {
        if ($outgoingLetter->status !== $from) {
            throw new \DomainException(sprintf(
                'Letter must have %s status.',
                $from->value,
            ));
        }

        return $this->repository->update($outgoingLetter, [
            ...$attributes,
            'status' => $to,
        ]);
    }
}
