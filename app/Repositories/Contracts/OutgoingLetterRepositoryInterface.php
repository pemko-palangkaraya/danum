<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Collection;

interface OutgoingLetterRepositoryInterface
{
    public function getAll(string $tenantId): Collection;

    public function find(string $id, string $tenantId): ?OutgoingLetter;

    public function findWithTrashed(string $id, string $tenantId): ?OutgoingLetter;

    public function create(array $data): OutgoingLetter;

    public function update(OutgoingLetter $outgoingLetter, array $data): OutgoingLetter;

    public function delete(OutgoingLetter $outgoingLetter): bool;

    public function restore(OutgoingLetter $outgoingLetter): bool;
}
