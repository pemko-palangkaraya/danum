<?php

declare(strict_types=1);

namespace App\Enums;

enum RegisterEntryStatus: string
{
    case REGISTERED = 'registered';
    case CORRECTED = 'corrected';
}
