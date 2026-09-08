<?php

declare(strict_types=1);

namespace App\Enums;

enum VerificationAccessLevel: string
{
    case PUBLIC = 'public';
    case PROTECTED = 'protected';
    case RESTRICTED = 'restricted';
}
