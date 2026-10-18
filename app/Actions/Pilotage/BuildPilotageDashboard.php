<?php

namespace App\Actions\Pilotage;

use App\Enums\ReportCardStatus;
use App\Models\ReportCard;
use App\Services\Academic\AcademicCalculationService;
use App\Services\Assistant\AssistantContext;
use App\Services\Assistant\SchoolAcademicService;
use App\Services\Assistant\SchoolAnalyticsService;
use App\Services\Assistant\SchoolAttendanceService;
use App\Services\Assistant\SchoolFinanceService;
use App\Services\Assistant\SchoolTeacherService;

/**
 * Construit la carte de pilotage affichée sur le tableau de bord.
 *
 * Réutilise les services du CoriSchool Assistant : les chiffres présentés ici
 * sont donc strictement identiques à ceux renvoyés par l'assistant, et
 * respectent le même périmètre (établissement ou primaire) ainsi que les
 * mêmes règles d'accès (finances réservées aux rôles habilités).
 */
class BuildPilotageDashboard
{
    public function handle(AssistantContext $context, string $prefix): array
    {
        $analytics = new SchoolAnalyticsService($context);
        $academic = new SchoolAcademicService($context, app(AcademicCalculationService::class));
        $attendance = new SchoolAttendanceService($context);
        $teachers = new SchoolTeacherService($context);

        $students = $analytics->studentCount();
        $overall = $academic->overall();
        $ranking = $academic->classRanking();
        $subjects = $academic->subjectAverages();
        $pendingEvaluations = $teachers->pendingEvaluations();
        $repeatedAbsences = $attendance->repeatedAbsences(3, now()->startOfMonth(), now()->endOfMonth());

        return [
            'scope_note' => $context->scopeNote(),
            'period' => $context->currentPeriod?->name,
            'effectifs' => [
                'students' => $students['active'],
                'girls' => $students['female'],
                'boys' => $students['male'],
                'classes' => $analytics->classCount(),
                'teachers' => $analytics->teacherCount(),
                'href' => "{$prefix}.students.index",
            ],
            'performance' => [
                'available' => $academic->hasData(),
                'average' => $overall['average'],
                'success_rate' => $overall['success_rate'],
                'evaluated' => $overall['evaluated'],
                'struggling' => $overall['struggling'],
                'best_classes' => array_slice($ranking, 0, 3),
                'weak_subjects' => array_slice($subjects, 0, 3),
                'href' => "{$prefix}.academic.results.index",
            ],
            'administration' => [
                'pending_evaluations' => count($pendingEvaluations),
                'pending_teachers' => $teachers->pendingTeacherCount(),
                'report_cards_to_validate' => $this->reportCardsToValidate($context),
                'important_absences' => count($repeatedAbsences),
                'href' => "{$prefix}.report-cards.index",
                'attendance_href' => "{$prefix}.attendance.index",
            ],
            'finance' => $context->canViewFinance
                ? $this->finance($context, $prefix)
                : null,
        ];
    }

    private function reportCardsToValidate(AssistantContext $context): int
    {
        return ReportCard::query()
            ->where('status', ReportCardStatus::Generated->value)
            ->when(
                $context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_room_id', $context->allowedClassIds),
            )
            ->count();
    }

    private function finance(AssistantContext $context, string $prefix): array
    {
        $finance = new SchoolFinanceService($context);
        $summary = $finance->summary('year');

        return [
            'revenue' => $summary['revenue'],
            'expenses' => $summary['expenses'],
            'balance' => $summary['balance'],
            'outstanding' => $finance->outstanding(),
            'href' => "{$prefix}.expenses.index",
        ];
    }
}
