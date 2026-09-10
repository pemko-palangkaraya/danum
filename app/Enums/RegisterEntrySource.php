<?php

declare(strict_types=1);

namespace App\Enums;

enum RegisterEntrySource: string
{
    case DANUM = 'danum';
    case MANUAL = 'manual';
}
