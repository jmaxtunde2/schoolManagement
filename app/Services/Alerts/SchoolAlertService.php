<?php

namespace App\Services\Alerts;

use App\Enums\AlertPriority;
use App\Enums\Role;
use App\Services\Academic\AcademicCalculationService;
use App\Services\Assistant\AssistantContext;
use App\Services\Assistant\SchoolAcademicService;
use App\Services\Assistant\SchoolAttendanceService;
use App\Services\Assistant\SchoolFinanceService;
use App\Services\Assistant\SchoolTeacherService;

/**
 * Génère les alertes de pilotage à partir des données de l'établissement.
 *
 * Chaque alerte est agrégée (un seul message par type, jamais un par élève)
 * afin de respecter la consigne « éviter de générer des centaines d'alertes ».
 * Les calculs réutilisent les services du CoriSchool Assistant, ce qui
 * garantit la cohérence entre l'assistant, le tableau de bord et les alertes.
 */
class SchoolAlertService
{
    public function __construct(private AssistantContext $context) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function collect(): array
    {
        $alerts = [];

        $academic = new SchoolAcademicService($this->context, app(AcademicCalculationService::class));
        $teachers = new SchoolTeacherService($this->context);
        $attendance = new SchoolAttendanceService($this->context);

        $threshold = (float) config('alerts.thresholds.average_below', 10.0);
        $minStruggling = (int) config('alerts.thresholds.struggling_students_min', 1);
        $struggling = $academic->studentsBelowAverage($threshold);

        if (count($struggling) >= $minStruggling) {
            $alerts[] = [
                'key' => 'academic.struggling_students',
                'type' => 'alert.academic',
                'priority' => count($struggling) >= 10 ? AlertPriority::Critical : AlertPriority::High,
                'title' => count($struggling).' élève(s) en difficulté',
                'message' => sprintf(
                    '%d élève(s) présentent une moyenne inférieure à %s/20 sur la période en cours.',
                    count($struggling),
                    rtrim(rtrim((string) $threshold, '0'), '.'),
                ),
                'route' => 'academic.results.index',
                'link_label' => 'Voir les résultats',
                'roles' => [Role::Admin, Role::Director],
            ];
        }

        $pendingTeachers = $teachers->pendingTeacherCount();
        if ($pendingTeachers > 0) {
            $alerts[] = [
                'key' => 'academic.pending_evaluations',
                'type' => 'alert.academic',
                'priority' => $pendingTeachers >= 3 ? AlertPriority::High : AlertPriority::Medium,
                'title' => $pendingTeachers.' enseignant(s) sans saisie complète',
                'message' => sprintf(
                    '%d enseignant(s) n\'ont pas encore saisi ou validé leurs dernières évaluations.',
                    $pendingTeachers,
                ),
                'route' => 'academic.results.index',
                'link_label' => 'Suivre les saisies',
                'roles' => [Role::Admin, Role::Director],
            ];
        }

        $absenceMin = (int) config('alerts.thresholds.absences_min', 3);
        $minAbsentees = (int) config('alerts.thresholds.absences_students_min', 1);
        $absentees = $attendance->repeatedAbsences($absenceMin, now()->startOfMonth(), now()->endOfMonth());

        if (count($absentees) >= $minAbsentees) {
            $alerts[] = [
                'key' => 'attendance.excessive_absences',
                'type' => 'alert.attendance',
                'priority' => count($absentees) >= 10 ? AlertPriority::High : AlertPriority::Medium,
                'title' => count($absentees).' élève(s) à absentéisme répété',
                'message' => sprintf(
                    '%d élève(s) ont enregistré au moins %d absences ce mois-ci.',
                    count($absentees),
                    $absenceMin,
                ),
                'route' => 'attendance.index',
                'link_label' => 'Voir l\'assiduité',
                'roles' => [Role::Admin, Role::Director],
            ];
        }

        $financeAlert = $this->financeAlert();
        if ($financeAlert !== null) {
            $alerts[] = $financeAlert;
        }

        return $alerts;
    }

    /** @return array<string, mixed>|null */
    private function financeAlert(): ?array
    {
        if (! $this->context->canViewFinance) {
            return null;
        }

        $period = (string) config('alerts.thresholds.finance_period', 'month');
        $finance = new SchoolFinanceService($this->context);
        $summary = $finance->summary($period);

        // Aucune alerte sans activité : évite un « dépenses > recettes » vide.
        if ($summary['expenses'] <= 0 || $summary['expenses'] <= $summary['revenue']) {
            return null;
        }

        $periodLabel = $period === 'month' ? 'du mois' : 'de l\'exercice';

        return [
            'key' => 'finance.deficit',
            'type' => 'alert.finance',
            'priority' => AlertPriority::Critical,
            'title' => 'Dépenses supérieures aux recettes',
            'message' => sprintf(
                'Les dépenses %s (%s) dépassent les recettes (%s).',
                $periodLabel,
                number_format($summary['expenses'], 0, ',', ' '),
                number_format($summary['revenue'], 0, ',', ' '),
            ),
            'route' => 'expenses.index',
            'link_label' => 'Ouvrir la finance',
            'roles' => [Role::Admin, Role::Accountant],
        ];
    }
}
