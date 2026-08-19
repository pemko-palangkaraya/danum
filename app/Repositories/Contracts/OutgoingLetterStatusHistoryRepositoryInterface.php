<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\OutgoingLetterStatusHistory;
use Illuminate\Database\Eloquent\Collection;

interface OutgoingLetterStatusHistoryRepositoryInterface
{
    public function getByOutgoingLetter(string $outgoingLetterId): Collection;

    public function create(array $data): OutgoingLetterStatusHistory;
}
