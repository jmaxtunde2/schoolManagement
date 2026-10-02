<?php

namespace App\Enums;

enum EvaluationType: string
{
    case Interrogation = 'interrogation';
    case Devoir = 'devoir';
    case Composition = 'composition';
    case Examen = 'examen';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Interrogation => 'Interrogation',
            self::Devoir => 'Devoir',
            self::Composition => 'Composition',
            self::Examen => 'Examen',
            self::Other => 'Autre',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }
}
