<?php

namespace App\Enums;

/**
 * Intentions comprises par le CoriSchool Assistant.
 *
 * Chaque intention correspond à un jeu de données contrôlé (services) et
 * jamais à une requête libre : l'assistant est une interface d'accès aux
 * données de l'établissement, pas une source de données (voir prompt phase 1).
 */
enum AssistantIntent: string
{
    case StudentCount = 'student_count';
    case StudentByClass = 'student_by_class';
    case ClassCount = 'class_count';
    case TeacherCount = 'teacher_count';
    case StudentsBelowAverage = 'students_below_average';
    case BestClass = 'best_class';
    case WorstClass = 'worst_class';
    case ClassAverage = 'class_average';
    case WeakestSubjects = 'weakest_subjects';
    case PendingEvaluations = 'pending_evaluations';
    case Revenue = 'revenue';
    case Expenses = 'expenses';
    case ExpensesByCategory = 'expenses_by_category';
    case Balance = 'balance';
    case RepeatedAbsences = 'repeated_absences';
    case AttendanceSummary = 'attendance_summary';
    case Help = 'help';

    /** Indique si l'intention expose des données financières sensibles. */
    public function isFinancial(): bool
    {
        return match ($this) {
            self::Revenue, self::Expenses, self::ExpensesByCategory, self::Balance => true,
            default => false,
        };
    }
}
