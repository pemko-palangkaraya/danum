<?php

declare(strict_types=1);

namespace App\Enums;

enum PositionAssignmentStatus: string
{
    case DEFINITIF = 'definitif';
    case PLT = 'plt';
    case PLH = 'plh';
    case PJ = 'pj';
    case PJS = 'pjs';

    public function label(): string
    {
        return match ($this) {
            self::DEFINITIF => 'Definitif',
            self::PLT => 'PLT (Pelaksana Tugas)',
            self::PLH => 'PLH (Pelaksana Harian)',
            self::PJ => 'Pj. (Penjabat)',
            self::PJS => 'Pjs. (Penjabat Sementara)',
        };
    }
}
