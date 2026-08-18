<?php

namespace App\Enums;

enum LetterTypeStatus: string
{
    case DRAFT = 'draft';
    case VALIDATED = 'validated';
    case ACTIVE = 'active';
    case RETIRED = 'retired';
}
