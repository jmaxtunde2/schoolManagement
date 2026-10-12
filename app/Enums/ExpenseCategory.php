<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case Fonctionnelle = 'fonctionnelle';
    case Salaire = 'salaire';
    case Fourniture = 'fourniture';
    case Materiel = 'materiel';
    case Entretien = 'entretien';
    case Communication = 'communication';
    case Transport = 'transport';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Fonctionnelle => 'Dépenses fonctionnelles',
            self::Salaire => 'Salaires',
            self::Fourniture => 'Fournitures',
            self::Materiel => 'Matériel & équipement',
            self::Entretien => 'Entretien & maintenance',
            self::Communication => 'Communication & publicité',
            self::Transport => 'Transport',
            self::Autre => 'Autres',
        };
    }

    /** @return array<int, self> */
    public static function createable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $category) => $category !== self::Salaire));
    }
}