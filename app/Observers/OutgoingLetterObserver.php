<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Services\CitizenDeathService;
use App\Services\RegisterEntryService;

final class OutgoingLetterObserver
{
    public function saved(OutgoingLetter $letter): void
    {
        if ($letter->status !== OutgoingLetterStatus::ISSUED) {
            return;
        }

        app(CitizenDeathService::class)->applyFromIssuedLetter($letter);
        app(RegisterEntryService::class)->registerIssuedLetter($letter->fresh(['letterType.classification']));
    }
}
