<?php

namespace App\Enums;

enum AlertPriority: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critique',
            self::High => 'Élevée',
            self::Medium => 'Moyenne',
            self::Low => 'Faible',
        };
    }

    /** Symbole d'emoji reproduisant la sémantique des alertes du cahier des charges. */
    public function icon(): string
    {
        return match ($this) {
            self::Critical => '🔴',
            self::High => '🟠',
            self::Medium => '🟡',
            self::Low => '🔵',
        };
    }
}
