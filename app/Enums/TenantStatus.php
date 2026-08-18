<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;
}