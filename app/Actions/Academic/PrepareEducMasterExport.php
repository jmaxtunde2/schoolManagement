<?php

namespace App\Actions\Academic;

use App\Models\AcademicPeriod;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Services\Academic\AcademicCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PrepareEducMasterExport
{
    public function __construct(
        private ClassRoom $classRoom,
        private AcademicPeriod $period,
        private AcademicCalculationService $calculator,
        private School $school,
    ) {}

    /**
     * Vérifie les données de la classe avant export au format EducMaster.
     * Ne prétend pas synchroniser avec EducMaster : il s'agit d'une préparation.
     *
     * @return array{
     *     ready: bool,
     *     class_name: string,
     *     period_name: string,
     *     academic_year_name: ?string,
     *     student_count: int,
     *     ranked_students: int,
     *     error_count: int,
     *     warning_count: int,
     *     issues: array<int, array{type: string, severity: string, message: string, student_id: ?int, matricule: ?string}>,
     *     checked_at: string
     * }
     */
    public function handle(): array
    {
        $results = $this->calculator->calculateClassResults(
            $this->classRoom,
            $this->period,
            (int) $this->school->id,
        );

        $students = Student::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->where('class_id', $this->classRoom->id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'matricule', 'first_name', 'last_name', 'birth_date', 'gender']);

        $rankedById = collect($results['students'])->keyBy('student_id');
        $issues = [];

        foreach ($students as $student) {
            $name = $this->studentName($student);

            if (blank($student->matricule)) {
                $issues[] = $this->issue('missing_matricule', 'error', "Matricule manquant pour {$name}.", $student->id, null);
            }

            if (blank($student->first_name) || blank($student->last_name)) {
                $issues[] = $this->issue('missing_name', 'error', "Nom ou prénom incomplet pour {$name}.", $student->id, $student->matricule);
            }

            if ($student->birth_date === null) {
                $issues[] = $this->issue('missing_birth_date', 'warning', "Date de naissance manquante pour {$name}.", $student->id, $student->matricule);
            }

            if (blank($student->gender)) {
                $issues[] = $this->issue('missing_gender', 'warning', "Sexe manquant pour {$name}.", $student->id, $student->matricule);
            }

            if (($rankedById->get($student->id)['general_average'] ?? null) === null) {
                $issues[] = $this->issue('no_results', 'warning', "Aucun résultat calculable pour {$name} (aucune évaluation validée).", $student->id, $student->matricule);
            }
        }

        if ($students->isNotEmpty() && ! $this->classHasSubjects()) {
            $issues[] = $this->issue(
                'class_without_subjects',
                'error',
                "La classe « {$this->classRoom->name} » n'a aucune matière associée.",
                null,
                null,
            );
        }

        foreach ($this->duplicateIdentities($students) as $identity => $group) {
            $issues[] = $this->issue(
                'duplicate_student',
                'warning',
                "Doublon possible : {$group->count()} élèves nommés « {$group->first()->last_name} {$group->first()->first_name} » nés le même jour.",
                null,
                null,
            );
        }

        $errorCount = collect($issues)->where('severity', 'error')->count();
        $warningCount = collect($issues)->where('severity', 'warning')->count();

        return [
            'ready' => $errorCount === 0,
            'class_name' => $this->classRoom->name,
            'period_name' => $this->period->name,
            'academic_year_name' => $results['academic_year_name'],
            'student_count' => $students->count(),
            'ranked_students' => $results['ranked_students'],
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'issues' => $issues,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function studentName(Student $student): string
    {
        $name = trim("{$student->last_name} {$student->first_name}");

        return $name !== '' ? $name : "Élève #{$student->id}";
    }

    private function classHasSubjects(): bool
    {
        return DB::table('class_subject')
            ->where('class_id', $this->classRoom->id)
            ->exists();
    }

    /**
     * @param  Collection<int, Student>  $students
     * @return Collection<string, Collection<int, Student>>
     */
    private function duplicateIdentities(Collection $students): Collection
    {
        return $students
            ->filter(fn (Student $student) => filled($student->last_name)
                && filled($student->first_name)
                && $student->birth_date !== null)
            ->groupBy(fn (Student $student) => mb_strtolower(
                trim($student->last_name).'|'.trim($student->first_name).'|'.$student->birth_date?->format('Y-m-d')
            ))
            ->filter(fn (Collection $group) => $group->count() > 1);
    }

    /**
     * @return array{type: string, severity: string, message: string, student_id: ?int, matricule: ?string}
     */
    private function issue(string $type, string $severity, string $message, ?int $studentId, ?string $matricule): array
    {
        return [
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'student_id' => $studentId,
            'matricule' => $matricule,
        ];
    }
}
