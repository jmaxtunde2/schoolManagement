<?php

namespace App\Actions\Academic\EducMaster;

use App\Enums\EvaluationStatus;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\Student;
use App\Models\Timetable;

/**
 * Synthèse de préparation : vérifie que les données de l'école sont
 * complètes avant un export au format EducMaster.
 */
class BuildEducMasterExportSummary
{
    /**
     * @return array{
     *     students: array{total: int, active: int, without_class: int, missing_matricule: int, missing_birth_date: int, missing_gender: int},
     *     classes: array{total: int, without_subjects: int},
     *     evaluations: array{total: int, pending: int},
     *     timetable: array{total: int},
     *     issue_count: int
     * }
     */
    /**
     * @param  array<int, int>|null  $classIds  Périmètre de classes (null = toutes).
     */
    public function handle(int $schoolId, ?array $classIds = null): array
    {
        $students = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($classIds !== null, fn ($query) => $query->whereIn('class_id', $classIds))
            ->get(['id', 'class_id', 'matricule', 'birth_date', 'gender', 'is_active']);

        $studentsTotal = $students->count();
        $studentsActive = $students->where('is_active', true)->count();
        $withoutClass = $students->whereNull('class_id')->count();
        $missingMatricule = $students->filter(fn (Student $student) => blank($student->matricule))->count();
        $missingBirthDate = $students->filter(fn (Student $student) => $student->birth_date === null)->count();
        $missingGender = $students->filter(fn (Student $student) => blank($student->gender))->count();

        $classesTotal = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($classIds !== null, fn ($query) => $query->whereIn('id', $classIds))
            ->count();
        $classesWithoutSubjects = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($classIds !== null, fn ($query) => $query->whereIn('id', $classIds))
            ->whereDoesntHave('subjects')
            ->count();

        $evaluationsTotal = Evaluation::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($classIds !== null, fn ($query) => $query->whereIn('class_id', $classIds))
            ->count();
        $evaluationsPending = Evaluation::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($classIds !== null, fn ($query) => $query->whereIn('class_id', $classIds))
            ->where('status', '!=', EvaluationStatus::Validated->value)
            ->count();

        $timetableTotal = Timetable::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($classIds !== null, fn ($query) => $query->whereIn('class_id', $classIds))
            ->count();

        $issueCount = $withoutClass
            + $missingMatricule
            + $missingBirthDate
            + $missingGender
            + $classesWithoutSubjects
            + $evaluationsPending;

        return [
            'students' => [
                'total' => $studentsTotal,
                'active' => $studentsActive,
                'without_class' => $withoutClass,
                'missing_matricule' => $missingMatricule,
                'missing_birth_date' => $missingBirthDate,
                'missing_gender' => $missingGender,
            ],
            'classes' => [
                'total' => $classesTotal,
                'without_subjects' => $classesWithoutSubjects,
            ],
            'evaluations' => [
                'total' => $evaluationsTotal,
                'pending' => $evaluationsPending,
            ],
            'timetable' => [
                'total' => $timetableTotal,
            ],
            'issue_count' => $issueCount,
        ];
    }
}
