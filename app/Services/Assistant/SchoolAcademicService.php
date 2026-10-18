<?php

namespace App\Services\Assistant;

use App\Models\AcademicPeriod;
use App\Services\Academic\AcademicCalculationService;
use Throwable;

/**
 * Résultats scolaires agrégés du périmètre courant (établissement ou primaire).
 *
 * S'appuie sur AcademicCalculationService afin de rester strictement cohérent
 * avec les moyennes affichées dans Résultats et les bulletins : seules les
 * évaluations validées sont prises en compte.
 */
class SchoolAcademicService
{
    /** @var array<int, array<string, mixed>>|null */
    private ?array $results = null;

    public function __construct(
        private AssistantContext $context,
        private AcademicCalculationService $calculator,
    ) {}

    public function period(): ?AcademicPeriod
    {
        return $this->context->currentPeriod;
    }

    /** Des notes validées existent-elles pour la période en cours ? */
    public function hasData(): bool
    {
        foreach ($this->resultsByClass() as $result) {
            foreach ($result['students'] as $student) {
                if ($student['general_average'] !== null) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Moyenne générale de chaque classe de la période courante, triée décroissante.
     *
     * @return array<int, array{class_id: int, class_name: string, average: float, students: int}>
     */
    public function classRanking(): array
    {
        $rows = [];

        foreach ($this->resultsByClass() as $classId => $result) {
            $averages = collect($result['students'])
                ->pluck('general_average')
                ->filter(fn ($value) => $value !== null);

            if ($averages->isEmpty()) {
                continue;
            }

            $rows[] = [
                'class_id' => (int) $classId,
                'class_name' => $result['class_name'],
                'average' => round((float) $averages->avg(), 2),
                'students' => $averages->count(),
            ];
        }

        usort($rows, fn (array $a, array $b) => $b['average'] <=> $a['average']);

        return $rows;
    }

    /**
     * Synthèse globale du périmètre : moyenne générale, taux de réussite
     * (part des élèves ayant au moins 10/20) et effectif évalué.
     *
     * @return array{evaluated: int, average: float|null, success_rate: float|null, struggling: int}
     */
    public function overall(): array
    {
        $averages = [];

        foreach ($this->resultsByClass() as $result) {
            foreach ($result['students'] as $student) {
                if ($student['general_average'] !== null) {
                    $averages[] = (float) $student['general_average'];
                }
            }
        }

        if ($averages === []) {
            return ['evaluated' => 0, 'average' => null, 'success_rate' => null, 'struggling' => 0];
        }

        $count = count($averages);
        $success = count(array_filter($averages, fn (float $average) => $average >= 10.0));

        return [
            'evaluated' => $count,
            'average' => round(array_sum($averages) / $count, 2),
            'success_rate' => round($success / $count * 100, 1),
            'struggling' => $count - $success,
        ];
    }

    /**
     * Moyenne par matière sur le périmètre, triée croissante (plus faible d'abord).
     *
     * @return array<int, array{subject: string, average: float, count: int}>
     */
    public function subjectAverages(): array
    {
        $sums = [];
        $counts = [];

        foreach ($this->resultsByClass() as $result) {
            foreach ($result['students'] as $student) {
                foreach ($student['subjects'] as $subject) {
                    $name = $subject['subject_name'];
                    $sums[$name] = ($sums[$name] ?? 0.0) + (float) $subject['average'];
                    $counts[$name] = ($counts[$name] ?? 0) + 1;
                }
            }
        }

        $rows = [];
        foreach ($sums as $name => $sum) {
            $rows[] = [
                'subject' => $name,
                'average' => round($sum / $counts[$name], 2),
                'count' => $counts[$name],
            ];
        }

        usort($rows, fn (array $a, array $b) => $a['average'] <=> $b['average']);

        return $rows;
    }

    /**
     * Élèves dont la moyenne générale est strictement inférieure au seuil.
     *
     * @return array<int, array{student: string, class: string, average: float}>
     */
    public function studentsBelowAverage(float $threshold): array
    {
        $rows = [];

        foreach ($this->resultsByClass() as $result) {
            foreach ($result['students'] as $student) {
                if ($student['general_average'] !== null && $student['general_average'] < $threshold) {
                    $rows[] = [
                        'student' => $student['student_name'],
                        'class' => $result['class_name'],
                        'average' => (float) $student['general_average'],
                    ];
                }
            }
        }

        usort($rows, fn (array $a, array $b) => $a['average'] <=> $b['average']);

        return $rows;
    }

    /**
     * Résultats calculés une seule fois par classe pour la période courante.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resultsByClass(): array
    {
        if ($this->results !== null) {
            return $this->results;
        }

        $this->results = [];
        $period = $this->period();

        if ($period === null) {
            return $this->results;
        }

        foreach ($this->context->classes() as $class) {
            try {
                $this->results[$class->id] = $this->calculator->calculateClassResults(
                    $class,
                    $period,
                    $this->context->schoolId,
                );
            } catch (Throwable) {
                // Classe sans matière affectée ou hors périmètre : ignorée.
            }
        }

        return $this->results;
    }
}
