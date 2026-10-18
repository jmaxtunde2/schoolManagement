<?php

namespace App\Actions\Students;

use App\Models\ClassRoom;
use App\Models\Student;

/**
 * Répartition des élèves actifs par sexe, globalement et par classe.
 *
 * Alimente le graphique « filles / garçons » de la page Classes et des tableaux
 * de bord. Le filtre par classe est appliqué côté client : la charge utile
 * contient toutes les classes en une seule agrégation SQL.
 */
class BuildGenderDistribution
{
    /**
     * @param  iterable<int>|null  $classIds  Restreint la distribution à ces classes.
     * @return array{totals: array<string, int|null>, classes: array<int, array<string, mixed>>}
     */
    public function handle(?iterable $classIds = null): array
    {
        $classIds = $classIds === null ? null : collect($classIds)->map(fn ($id) => (int) $id)->all();

        $counts = Student::query()
            ->where('is_active', true)
            ->when($classIds !== null, fn ($query) => $query->whereIn('class_id', $classIds))
            ->selectRaw('class_id, gender, count(*) as total')
            ->groupBy('class_id', 'gender')
            ->get();

        $byClass = [];
        $totals = ['students' => 0, 'female' => 0, 'male' => 0, 'unspecified' => 0];

        foreach ($counts as $count) {
            $total = (int) $count->total;
            $gender = $this->bucket($count->gender);

            $byClass[$count->class_id][$gender] = ($byClass[$count->class_id][$gender] ?? 0) + $total;

            $totals['students'] += $total;
            $totals[$gender] += $total;
        }

        $classes = ClassRoom::query()
            ->when($classIds !== null, fn ($query) => $query->whereIn('id', $classIds))
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        $rows = [];

        foreach ($classes as $class) {
            $rows[] = $this->row($class->id, $class->name, $class->level, $byClass[$class->id] ?? []);
        }

        if ($classIds === null && ! empty($byClass[null])) {
            $rows[] = $this->row(null, 'Élèves non affectés', null, $byClass[null]);
        }

        return [
            'totals' => $this->summary($totals),
            'classes' => $rows,
        ];
    }

    private function bucket(?string $gender): string
    {
        return match ($gender) {
            'F' => 'female',
            'M' => 'male',
            default => 'unspecified',
        };
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, mixed>
     */
    private function row(?int $id, string $name, ?string $level, array $counts): array
    {
        $female = $counts['female'] ?? 0;
        $male = $counts['male'] ?? 0;
        $total = $female + $male + ($counts['unspecified'] ?? 0);

        return [
            'key' => $id === null ? 'unassigned' : 'class-'.$id,
            'id' => $id,
            'name' => $name,
            'level' => $level,
            'total' => $total,
            'female' => $female,
            'male' => $male,
            'unspecified' => $counts['unspecified'] ?? 0,
            'female_rate' => $total > 0 ? (int) round($female / $total * 100) : null,
            'male_rate' => $total > 0 ? (int) round($male / $total * 100) : null,
        ];
    }

    /**
     * @param  array<string, int>  $totals
     * @return array<string, int|null>
     */
    private function summary(array $totals): array
    {
        return [
            'students' => $totals['students'],
            'female' => $totals['female'],
            'male' => $totals['male'],
            'unspecified' => $totals['unspecified'],
            'female_rate' => $totals['students'] > 0 ? (int) round($totals['female'] / $totals['students'] * 100) : null,
            'male_rate' => $totals['students'] > 0 ? (int) round($totals['male'] / $totals['students'] * 100) : null,
        ];
    }
}
