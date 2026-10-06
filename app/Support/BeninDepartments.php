<?php

namespace App\Support;

/**
 * Départements du Bénin. Utilisé par l'inscription d'école et les paramètres
 * d'établissement, où `city` seul ne suffit pas à situer un établissement.
 */
class BeninDepartments
{
    public const DEPARTMENTS = [
        'Alibori' => 'Alibori',
        'Atacora' => 'Atacora',
        'Atlantique' => 'Atlantique',
        'Borgou' => 'Borgou',
        'Collines' => 'Collines',
        'Couffo' => 'Couffo',
        'Donga' => 'Donga',
        'Littoral' => 'Littoral',
        'Mono' => 'Mono',
        'Ouémé' => 'Ouémé',
        'Plateau' => 'Plateau',
        'Zou' => 'Zou',
    ];

    /** @return array<string, string> valeur => libellé */
    public static function options(): array
    {
        return self::DEPARTMENTS;
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && array_key_exists($value, self::DEPARTMENTS);
    }
}
