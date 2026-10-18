<?php

namespace App\Enums;

enum ExamResourceType: string
{
    case Epreuve = 'epreuve';
    case Exercice = 'exercice';
    case Devoir = 'devoir';
    case ExamenBlanc = 'examen_blanc';
    case Qcm = 'qcm';
    case Quiz = 'quiz';
    case Corrige = 'corrige';
    case SujetExamen = 'sujet_examen';
    case FicheExercices = 'fiche_exercices';

    public function label(): string
    {
        return match ($this) {
            self::Epreuve => 'Épreuve',
            self::Exercice => 'Exercice',
            self::Devoir => 'Devoir',
            self::ExamenBlanc => 'Examen blanc',
            self::Qcm => 'QCM',
            self::Quiz => 'Quiz',
            self::Corrige => 'Corrigé',
            self::SujetExamen => 'Sujet d\'examen',
            self::FicheExercices => 'Fiche d\'exercices',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $c) => ['value' => $c->value, 'label' => $c->label()], self::cases());
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
