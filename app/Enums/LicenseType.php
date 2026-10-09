<?php

namespace App\Enums;

enum LicenseType: string
{
    case Initial = 'initial';
    case Renewal = 'renewal';

    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Licence initiale',
            self::Renewal => 'Renouvellement',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Initial => 'Première année : activation + année de licence.',
            self::Renewal => 'Années suivantes : renouvellement annuel.',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}