<?php

namespace App\Enums;

enum AttendanceValidationStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Validated => 'Validé',
        };
    }
}
