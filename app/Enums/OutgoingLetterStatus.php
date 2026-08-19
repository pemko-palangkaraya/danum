<?php

declare(strict_types=1);

namespace App\Enums;

enum OutgoingLetterStatus: string
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case ISSUED = 'issued';
    case CANCELLED = 'cancelled';
}
