<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OutgoingLetter;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OutgoingLetterRepository implements OutgoingLetterRepositoryInterface
{
    public function getAll(string $tenantId): Collection
    {
        return OutgoingLetter::query()
            ->where('tenant_id', $tenantId)
            ->latest('created_at')
            ->get();
    }

    public function find(string $id, string $tenantId): ?OutgoingLetter
    {
        return OutgoingLetter::query()
            ->where('tenant_id', $tenantId)
            ->find($id);
    }

    public function findWithTrashed(string $id, string $tenantId): ?OutgoingLetter
    {
        return OutgoingLetter::withTrashed()
            ->where('tenant_id', $tenantId)
            ->find($id);
    }

    public function create(array $data): OutgoingLetter
    {
        return OutgoingLetter::query()->create($data);
    }

    public function update(OutgoingLetter $outgoingLetter, array $data): OutgoingLetter
    {
        $outgoingLetter->update($data);

        return $outgoingLetter->refresh();
    }

    public function delete(OutgoingLetter $outgoingLetter): bool
    {
        return $outgoingLetter->delete();
    }

    public function restore(OutgoingLetter $outgoingLetter): bool
    {
        return $outgoingLetter->restore();
    }
}
