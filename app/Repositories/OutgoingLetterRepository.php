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
        return OutgoingLetter::query()->where('tenant_id', $tenantId)->latest('created_at')->get();
    }

    public function find(string $id, string $tenantId): ?OutgoingLetter
    {
        return OutgoingLetter::query()->where('tenant_id', $tenantId)->find($id);
    }

    public function findWithTrashed(string $id, ?string $tenantId = null): ?OutgoingLetter
    {
        return OutgoingLetter::withTrashed()
            ->when($tenantId !== null, fn ($query) => $query->where('tenant_id', $tenantId))
            ->find($id);
    }

    public function create(array $data): OutgoingLetter
    {
        return OutgoingLetter::query()->create($this->normalizeLetterData($data));
    }

    public function update(OutgoingLetter $outgoingLetter, array $data): OutgoingLetter
    {
        $outgoingLetter->update($this->normalizeLetterData($data));
        return $outgoingLetter->refresh();
    }

    public function delete(OutgoingLetter $outgoingLetter): bool { return $outgoingLetter->delete(); }
    public function restore(OutgoingLetter $outgoingLetter): bool { return $outgoingLetter->restore(); }

    private function normalizeLetterData(array $data): array
    {
        if (isset($data['input_data']) && is_array($data['input_data'])) {
            $inputData = $data['input_data'];
            if (isset($inputData['recipient_age']) && is_numeric($inputData['recipient_age'])) {
                $inputData['recipient_age'] = (string) floor((float) $inputData['recipient_age']);
            }
            $data['input_data'] = $inputData;
        }

        return $data;
    }
}
