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
 * Le calcul s'appuie sur AcademicCalculationService afin de rester cohérent
 * avec la moyenne affichée dans la liste des résultats.
 */
class BuildResultsPerformance
{
    public const MODES = ['periods', 'years'];

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

            $sum = 0.0;
            $count = 0;

            foreach ($periods as $period) {
                $bucket = $this->bucket($classes, $period, $schoolId);
                $sum += $bucket['sum'];
                $count += $bucket['count'];
            }

            return [
                'key' => 'year-'.$year->id,
                'label' => $year->name,
                'average' => $count > 0 ? round($sum / $count, 2) : null,
                'students' => $count,
                'academic_year' => $year->name,
            ];
        })->values()->all();
    }

    /**
     * Moyennes cumulées des élèves d'un ensemble de classes sur une période.
     *
     * @param  Collection<int, ClassRoom>  $classes
     * @return array{sum: float, count: int, average: float|null}
     */
    private function bucket(Collection $classes, AcademicPeriod $period, int $schoolId): array
    {
        $sum = 0.0;
        $count = 0;

        foreach ($classes as $classRoom) {
            $results = $this->calculator->calculateClassResults($classRoom, $period, $schoolId);

            foreach ($results['students'] as $student) {
                if ($student['general_average'] !== null) {
                    $sum += (float) $student['general_average'];
                    $count++;
                }
            }
        }

        return [
            'sum' => $sum,
            'count' => $count,
            'average' => $count > 0 ? round($sum / $count, 2) : null,
        ];
    }
}
