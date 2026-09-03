<?php

declare(strict_types=1);

namespace App\Enums;

enum PositionType: string
{
    case MANAGERIAL = 'managerial';
    case JFU = 'jfu';
    case JFT = 'jft';

    public function label(): string
    {
        return match ($this) {
            self::MANAGERIAL => 'Manajerial',
            self::JFU => 'Jabatan Fungsional Umum (JFU)',
            self::JFT => 'Jabatan Fungsional Tertentu (JFT)',
        };
    }

    public function allowsMultipleActiveHolders(): bool
    {
        return $this !== self::MANAGERIAL;
    }
}
