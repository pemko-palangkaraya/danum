<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LetterType;
use App\Services\LetterTypeNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class LetterTypeObserver implements ShouldHandleEventsAfterCommit
{
    public function deleted(LetterType $letterType): void
    {
        app(LetterTypeNotificationService::class)->notifyDeleted($letterType);
    }

    public function restored(LetterType $letterType): void
    {
        app(LetterTypeNotificationService::class)->notifyRestored($letterType);
    }
}
