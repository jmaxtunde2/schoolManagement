<?php

namespace App\Actions\Performance;

use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Services\Academic\AcademicCalculationService;
use App\Services\Assistant\AssistantContext;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Performance scolaire (phase 4) : moyennes par classe et par matière, taux de
 * réussite, élèves en difficulté, évolution et comparaison des périodes.
 *
 * Tous les calculs reposent sur AcademicCalculationService : seules les
 * évaluations validées sont prises en compte, ce qui garantit la cohérence
 * avec les bulletins et l'assistant.
 */
class BuildAcademicPerformance
{
    public function __construct(private AcademicCalculationService $calculator) {}

    /** @return array<string, mixed> */
    public function handle(AssistantContext $context): array
    {
        $classes = $context->classes();
        $periods = $this->periods($context);
        $threshold = (float) config('alerts.thresholds.average_below', 10.0);

        $evolution = [];
        $currentDetails = null;
        $currentPeriodId = $context->currentPeriod?->id;
        $hasData = false;

        foreach ($periods as $period) {
            $isCurrent = $period->id === $currentPeriodId;
            $summary = $this->summarize($classes, $period, $context->schoolId, $isCurrent, $threshold);

            if ($summary['evaluated'] > 0) {
                $hasData = true;
            }

            $evolution[] = [
                'id' => $period->id,
                'name' => $period->name,
                'average' => $summary['average'],
                'success_rate' => $summary['success_rate'],
                'evaluated' => $summary['evaluated'],
                'is_current' => $isCurrent,
            ];

            if ($isCurrent) {
                $currentDetails = $summary;
            }
        }

        return [
            'available' => $hasData,
            'threshold' => $threshold,
            'period_name' => $context->currentPeriod?->name,
            'overall' => [
                'average' => $currentDetails['average'] ?? null,
                'success_rate' => $currentDetails['success_rate'] ?? null,
                'evaluated' => $currentDetails['evaluated'] ?? 0,
                'struggling' => $currentDetails['struggling'] ?? 0,
            ],
            'by_class' => $currentDetails['by_class'] ?? [],
            'by_subject' => $currentDetails['by_subject'] ?? [],
            'struggling_students' => $currentDetails['struggling_students'] ?? [],
            'top_students' => $currentDetails['top_students'] ?? [],
            'evolution' => $evolution,
            'scope_note' => $context->scopeNote(),
        ];
    }

    /**
     * @param  Collection<int, ClassRoom>  $classes
     * @return array<string, mixed>
     */
    private function summarize($classes, AcademicPeriod $period, int $schoolId, bool $withDetails, float $threshold): array
    {
        $averages = [];
        $classRows = [];
        $subjectSums = [];
        $subjectCounts = [];
        $struggling = [];
        $top = [];

        foreach ($classes as $class) {
            try {
                $result = $this->calculator->calculateClassResults($class, $period, $schoolId);
            } catch (Throwable) {
                continue;
            }

            $classAverages = [];

            foreach ($result['students'] as $student) {
                $average = $student['general_average'];

                if ($average === null) {
                    continue;
                }

                $average = (float) $average;
                $averages[] = $average;
                $classAverages[] = $average;

                if ($withDetails) {
                    foreach ($student['subjects'] as $subject) {
                        $name = $subject['subject_name'];
                        $subjectSums[$name] = ($subjectSums[$name] ?? 0.0) + (float) $subject['average'];
                        $subjectCounts[$name] = ($subjectCounts[$name] ?? 0) + 1;
                    }

                    if ($average < $threshold) {
                        $struggling[] = [
                            'student' => $student['student_name'],
                            'class' => $class->name,
                            'average' => $average,
                        ];
                    } else {
                        $top[] = [
                            'student' => $student['student_name'],
                            'class' => $class->name,
                            'average' => $average,
                        ];
                    }
                }
            }

            if ($classAverages !== []) {
                $classRows[] = [
                    'class_id' => (int) $class->id,
                    'class_name' => $class->name,
                    'average' => round(array_sum($classAverages) / count($classAverages), 2),
                    'students' => count($classAverages),
                ];
            }
        }

        $count = count($averages);
        $success = count(array_filter($averages, fn (float $value) => $value >= 10.0));

        usort($classRows, fn ($a, $b) => $b['average'] <=> $a['average']);

        $subjectRows = [];
        foreach ($subjectSums as $name => $sum) {
            $subjectRows[] = [
                'subject' => $name,
                'average' => round($sum / $subjectCounts[$name], 2),
            ];
        }
        usort($subjectRows, fn ($a, $b) => $a['average'] <=> $b['average']);

        usort($struggling, fn ($a, $b) => $a['average'] <=> $b['average']);

        usort($top, fn ($a, $b) => $b['average'] <=> $a['average']);

        return [
            'evaluated' => $count,
            'average' => $count > 0 ? round(array_sum($averages) / $count, 2) : null,
            'success_rate' => $count > 0 ? round($success / $count * 100, 1) : null,
            'struggling' => $count - $success,
            'by_class' => $classRows,
            'by_subject' => $withDetails ? $subjectRows : [],
            'struggling_students' => $withDetails ? array_slice($struggling, 0, 20) : [],
            'top_students' => $withDetails ? array_slice($top, 0, 10) : [],
        ];
    }

    /**
     * @return Collection<int, AcademicPeriod>
     */
    private function periods(AssistantContext $context)
    {
        $year = $context->academicYear;

        if ($year === null) {
            return collect();
        }

        return AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $context->schoolId)
            ->where('academic_year_id', $year->id)
            ->orderBy('position')
            ->get();
    }
}
