<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\UserStatus;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusChanged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly UserStatus $oldStatus,
        public readonly UserStatus $newStatus,
        public readonly DateTimeInterface $changedAt,
    ) {}
}
