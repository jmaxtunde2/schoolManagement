<?php

namespace App\Actions\Academic;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Services\Academic\AcademicCalculationService;
use Illuminate\Support\Collection;

/**
 * Performance académique agrégée pour le graphique de la page « Résultats ».
 *
 * Deux modes :
 *  - « periods » : moyenne générale de la portée (établissement ou une classe)
 *    le long des trimestres / semestres de l'année sélectionnée ;
 *  - « years »   : moyenne générale cumulée de chaque année académique.
 *
 * Chaque point porte aussi la répartition des élèves (excellent, bien, assez,
 * passable, échec) et les taux de réussite / d'échec (seuil de 10/20).
 *
 * Le calcul s'appuie sur AcademicCalculationService afin de rester cohérent
 * avec la moyenne affichée dans la liste des résultats.
 */
class BuildResultsPerformance
{
    public const MODES = ['periods', 'years'];

    /** Bornes inférieures des mentions (le seuil de réussite est 10/20). */
    public const BRACKETS = [
        'excellent' => 16.0,
        'bien' => 14.0,
        'assez' => 12.0,
        'passable' => 10.0,
    ];

    public function __construct(private AcademicCalculationService $calculator) {}

    /**
     * @param  array<int, int>  $allowedClassIds  Classes visibles par l'utilisateur.
     * @return array<string, mixed>
     */
    public function handle(
        int $schoolId,
        array $allowedClassIds,
        string $mode,
        ?int $classId = null,
        ?int $academicYearId = null,
    ): array {
        $mode = in_array($mode, self::MODES, true) ? $mode : 'periods';

        $scopeClassIds = $classId !== null
            ? array_values(array_intersect([$classId], $allowedClassIds))
            : array_values($allowedClassIds);

        $classes = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereIn('id', $scopeClassIds)
            ->get(['id', 'name', 'school_id'])
            ->keyBy('id');

        $scopeLabel = $classId !== null
            ? ($classes->get($classId)?->name ?? 'Classe')
            : 'Tout l’établissement';

        $year = $this->resolveYear($schoolId, $academicYearId);

        $series = $mode === 'periods'
            ? $this->periodSeries($schoolId, $classes, $year)
            : $this->yearSeries($schoolId, $classes);

        $averages = collect($series)->pluck('average')->filter(fn ($value) => $value !== null);

        return [
            'mode' => $mode,
            'scope_label' => $scopeLabel,
            'academic_year' => $year?->name,
            'series' => $series,
            'overall' => $averages->isNotEmpty() ? round((float) $averages->avg(), 2) : null,
            'max' => 20,
        ];
    }

    private function resolveYear(int $schoolId, ?int $academicYearId): ?AcademicYear
    {
        $query = AcademicYear::withoutGlobalScopes()->where('school_id', $schoolId);

        if ($academicYearId !== null) {
            return (clone $query)->find($academicYearId);
        }

        return (clone $query)->where('is_current', true)->first()
            ?? (clone $query)->orderByDesc('starts_on')->first();
    }

    /**
     * @param  Collection<int, ClassRoom>  $classes
     * @return array<int, array<string, mixed>>
     */
    private function periodSeries(int $schoolId, Collection $classes, ?AcademicYear $year): array
    {
        if ($year === null) {
            return [];
        }

        $periods = AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $year->id)
            ->orderBy('position')
            ->get(['id', 'name', 'academic_year_id', 'school_id']);

        return $periods->map(function (AcademicPeriod $period) use ($classes, $schoolId, $year) {
            $bucket = $this->bucket($classes, $period, $schoolId);

            return [
                'key' => 'period-'.$period->id,
                'label' => $period->name,
                'average' => $bucket['average'],
                'students' => $bucket['count'],
                'distribution' => $this->distribution($bucket['averages']),
                'academic_year' => $year->name,
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, ClassRoom>  $classes
     * @return array<int, array<string, mixed>>
     */
    private function yearSeries(int $schoolId, Collection $classes): array
    {
        $years = AcademicYear::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->orderBy('starts_on')
            ->get(['id', 'name']);

        return $years->map(function (AcademicYear $year) use ($classes, $schoolId) {
            $periods = AcademicPeriod::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $year->id)
                ->get(['id', 'name', 'academic_year_id', 'school_id']);

            $byStudent = [];

            foreach ($periods as $period) {
                foreach ($classes as $classRoom) {
                    $results = $this->calculator->calculateClassResults($classRoom, $period, $schoolId);

                    foreach ($results['students'] as $student) {
                        if ($student['general_average'] !== null) {
                            $byStudent[$student['student_id']][] = (float) $student['general_average'];
                        }
                    }
                }
            }

            $averages = array_map(
                fn (array $values) => array_sum($values) / count($values),
                $byStudent,
            );
            $count = count($averages);

            return [
                'key' => 'year-'.$year->id,
                'label' => $year->name,
                'average' => $count > 0 ? round(array_sum($averages) / $count, 2) : null,
                'students' => $count,
                'distribution' => $this->distribution($averages),
                'academic_year' => $year->name,
            ];
        })->values()->all();
    }

    /**
     * Moyennes cumulées des élèves d'un ensemble de classes sur une période.
     *
     * @param  Collection<int, ClassRoom>  $classes
     * @return array{sum: float, count: int, average: float|null, averages: array<int, float>}
     */
    private function bucket(Collection $classes, AcademicPeriod $period, int $schoolId): array
    {
        $averages = [];

        foreach ($classes as $classRoom) {
            $results = $this->calculator->calculateClassResults($classRoom, $period, $schoolId);

            foreach ($results['students'] as $student) {
                if ($student['general_average'] !== null) {
                    $averages[] = (float) $student['general_average'];
                }
            }
        }

        $count = count($averages);

        return [
            'sum' => array_sum($averages),
            'count' => $count,
            'average' => $count > 0 ? round(array_sum($averages) / $count, 2) : null,
            'averages' => $averages,
        ];
    }

    /**
     * Répartition des moyennes par mention et taux de réussite / d'échec.
     *
     * @param  array<int, float>  $averages
     * @return array<string, int|null>
     */
    private function distribution(array $averages): array
    {
        $brackets = [
            'excellent' => 0,
            'bien' => 0,
            'assez' => 0,
            'passable' => 0,
            'echec' => 0,
        ];

        foreach ($averages as $average) {
            $brackets[match (true) {
                $average >= self::BRACKETS['excellent'] => 'excellent',
                $average >= self::BRACKETS['bien'] => 'bien',
                $average >= self::BRACKETS['assez'] => 'assez',
                $average >= self::BRACKETS['passable'] => 'passable',
                default => 'echec',
            }]++;
        }

        $graded = count($averages);
        $success = $brackets['excellent'] + $brackets['bien'] + $brackets['assez'] + $brackets['passable'];
        $failure = $brackets['echec'];

        return [
            ...$brackets,
            'graded' => $graded,
            'success' => $success,
            'failure' => $failure,
            'success_rate' => $graded > 0 ? (int) round($success / $graded * 100) : null,
            'failure_rate' => $graded > 0 ? (int) round($failure / $graded * 100) : null,
        ];
    }
}
