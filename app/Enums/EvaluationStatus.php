<?php

namespace App\Enums;

enum EvaluationStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Validated = 'validated';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::InProgress => 'En cours de validation',
            self::Validated => 'Validée',
            self::Returned => 'Retour pour correction',
        };
    }
}
