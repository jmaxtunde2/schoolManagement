<?php

namespace App\Enums;

enum SaleCategory: string
{
    case Uniforme = 'uniforme';
    case Manuel = 'manuel';
    case Fourniture = 'fourniture';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Uniforme => 'Uniforme',
            self::Manuel => 'Manuel scolaire',
            self::Fourniture => 'Fourniture',
            self::Autre => 'Autre',
        };
    }
}