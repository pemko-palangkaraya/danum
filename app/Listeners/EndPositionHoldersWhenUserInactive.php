<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserStatus;
use App\Events\UserStatusChanged;
use App\Repositories\Contracts\PositionHolderRepositoryInterface;

class EndPositionHoldersWhenUserInactive
{
    public function __construct(
        private readonly PositionHolderRepositoryInterface $positionHolderRepository,
    ) {}

    public function handle(UserStatusChanged $event): void
    {
        if ($event->newStatus !== UserStatus::INACTIVE) {
            return;
        }

        $holders = $this->positionHolderRepository
            ->getActiveByUserId($event->user->id);

        foreach ($holders as $holder) {
            $this->positionHolderRepository->end(
                $holder,
                $event->changedAt
            );
        }
    }
}
