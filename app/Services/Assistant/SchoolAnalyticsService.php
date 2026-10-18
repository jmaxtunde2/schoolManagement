<?php

namespace App\Services\Assistant;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Teacher;

/**
 * Chiffres clés de l'établissement : effectifs, classes et enseignants.
 *
 * Toutes les requêtes sont bornées au `school_id` du contexte (via le scope
 * global des modèles) et, le cas échéant, aux classes autorisées.
 */
class SchoolAnalyticsService
{
    public function __construct(private AssistantContext $context) {}

    /**
     * @return array{active: int, total: int, female: int, male: int}
     */
    public function studentCount(): array
    {
        $scope = fn () => Student::query()->when(
            $this->context->allowedClassIds !== null,
            fn ($query) => $query->whereIn('class_id', $this->context->allowedClassIds),
        );

        $active = $scope()->where('is_active', true);

        return [
            'active' => (clone $active)->count(),
            'total' => $scope()->count(),
            'female' => (clone $active)->where('gender', 'F')->count(),
            'male' => (clone $active)->where('gender', 'M')->count(),
        ];
    }

    /**
     * Effectif des élèves actifs, classe par classe.
     *
     * @return array<int, array{id: int, name: string, total: int}>
     */
    public function distributionByClass(): array
    {
        $counts = Student::query()
            ->where('is_active', true)
            ->when(
                $this->context->allowedClassIds !== null,
                fn ($query) => $query->whereIn('class_id', $this->context->allowedClassIds),
            )
            ->selectRaw('class_id, count(*) as total')
            ->groupBy('class_id')
            ->pluck('total', 'class_id');

        return $this->context->classes()
            ->map(fn (ClassRoom $class) => [
                'id' => $class->id,
                'name' => $class->name,
                'total' => (int) ($counts[$class->id] ?? 0),
            ])
            ->values()
            ->all();
    }

    public function classCount(): int
    {
        return $this->context->classes()->count();
    }

    public function teacherCount(): int
    {
        return Teacher::query()
            ->when(
                $this->context->allowedClassIds !== null,
                fn ($query) => $query->whereHas(
                    'assignments.classRoom',
                    fn ($classQuery) => $classQuery->whereIn('id', $this->context->allowedClassIds),
                ),
            )
            ->count();
    }
}
