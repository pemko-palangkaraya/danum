<?php

declare(strict_types=1);

namespace App\Enums;

enum OutgoingLetterWithdrawalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
