<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\OutgoingLetterStatusHistory;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OutgoingLetterStatusHistoryRepository implements OutgoingLetterStatusHistoryRepositoryInterface
{
    public function getByOutgoingLetter(string $outgoingLetterId): Collection
    {
        return OutgoingLetterStatusHistory::query()
            ->where('outgoing_letter_id', $outgoingLetterId)
            ->orderBy('created_at')
            ->get();
    }

    public function create(array $data): OutgoingLetterStatusHistory
    {
        return OutgoingLetterStatusHistory::query()->create($data);
    }
}
