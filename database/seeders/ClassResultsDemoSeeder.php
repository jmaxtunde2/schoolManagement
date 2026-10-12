<?php

namespace Database\Seeders;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\StudentContribution;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherClassSubject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Génère des notes fictives pour tester les résultats académiques et les bulletins.
 *
 * Par défaut : classe « 6ème A » de l'école Ketry School (id 3), toutes les matières
 * de l'école, avec les coefficients du gabarit d'import. Chaque matière reçoit quatre
 * évaluations validées (deux interrogations et deux devoirs) et une note aléatoire pour
 * chaque élève, ce qui permet de générer des bulletins.
 *
 * Exécution ciblée :
 *   php artisan db:seed --class=ClassResultsDemoSeeder
 *
 * Cibler une autre classe/école (dans un tinker) :
 *   (new \Database\Seeders\ClassResultsDemoSeeder(schoolId: 3, className: '5ème B'))->run();
 */
class ClassResultsDemoSeeder extends Seeder
{
    /** Coefficients souhaités par code matière (repli : 2.0). */
    private const COEFFICIENTS = [
        'MATH' => 3.0,
        'FR' => 3.0,
        'ANG' => 2.0,
        'SVT' => 2.0,
        'SPCT' => 2.0,
        'PHIL' => 2.0,
        'Info' => 2.0,
        'Hist-Geo' => 2.0,
    ];

    public function __construct(
        private ?int $schoolId = 3,
        private string $className = '6ème A',
    ) {}

    public function run(): void
    {
        $school = $this->schoolId !== null
            ? School::findOrFail($this->schoolId)
            : School::orderBy('id')->firstOrFail();

        $classRoom = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('name', $this->className)
            ->firstOrFail();

        $academicYear = AcademicYear::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('is_current', true)
            ->first()
            ?? AcademicYear::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->orderByDesc('id')
                ->firstOrFail();

        $period = AcademicPeriod::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('is_closed', false)
            ->orderBy('position')
            ->first()
            ?? AcademicPeriod::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->where('academic_year_id', $academicYear->id)
                ->orderBy('position')
                ->firstOrFail();

        $subjects = Subject::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->orderBy('name')
            ->get();

        $students = $classRoom->students()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($subjects->isEmpty() || $students->isEmpty()) {
            $this->command?->warn('Aucune matière ou aucun élève actif : rien à générer.');

            return;
        }

        $teachers = Teacher::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->orderBy('id')
            ->get();

        $actorId = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereIn('role', ['admin', 'censeur'])
            ->orderBy('id')
            ->value('id')
            ?? User::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->orderBy('id')
                ->value('id');

        // 1) Affecte toutes les matières de l'école à la classe, avec leurs coefficients.
        $pivot = [];
        foreach ($subjects as $subject) {
            $pivot[$subject->id] = ['default_coefficient' => self::COEFFICIENTS[$subject->code] ?? 2.0];
        }
        $classRoom->subjects()->syncWithoutDetaching($pivot);
        foreach ($pivot as $subjectId => $attributes) {
            $classRoom->subjects()->updateExistingPivot($subjectId, $attributes);
        }

        // 2) Évaluations validées par matière, avec les notes de chaque élève.
        $blueprint = [
            ['title' => 'Interrogation 1', 'type' => EvaluationType::Interrogation, 'coefficient' => 1.0, 'offset_days' => 28],
            ['title' => 'Interrogation 2', 'type' => EvaluationType::Interrogation, 'coefficient' => 1.0, 'offset_days' => 21],
            ['title' => 'Devoir 1', 'type' => EvaluationType::Devoir, 'coefficient' => 2.0, 'offset_days' => 14],
            ['title' => 'Devoir 2', 'type' => EvaluationType::Devoir, 'coefficient' => 2.0, 'offset_days' => 7],
        ];

        $evaluationCount = 0;
        $resultCount = 0;

        foreach ($subjects as $index => $subject) {
            $teacher = $this->resolveTeacher($classRoom, $subject, $teachers, $index);

            foreach ($blueprint as $slot) {
                $evaluation = Evaluation::withoutGlobalScopes()->updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'class_id' => $classRoom->id,
                        'subject_id' => $subject->id,
                        'academic_year_id' => $academicYear->id,
                        'academic_period_id' => $period->id,
                        'title' => $slot['title'],
                    ],
                    [
                        'teacher_id' => $teacher?->id,
                        'type' => $slot['type']->value,
                        'evaluation_date' => $period->ends_at
                            ? $period->ends_at->copy()->subDays($slot['offset_days'])
                            : now()->subDays($slot['offset_days']),
                        'max_score' => 20,
                        'coefficient' => $slot['coefficient'],
                        'status' => EvaluationStatus::Validated->value,
                        'validated_at' => now(),
                        'validated_by' => $actorId,
                        'created_by' => $actorId,
                    ],
                );

                if (! $evaluation->isValidated()) {
                    $evaluation->update([
                        'status' => EvaluationStatus::Validated->value,
                        'validated_at' => now(),
                        'validated_by' => $actorId,
                    ]);
                }

                $evaluationCount++;

                foreach ($students as $student) {
                    $evaluation->results()->updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'school_id' => $school->id,
                            'score' => random_int(8, 19),
                            'is_absent' => false,
                            'updated_by' => $actorId,
                        ],
                    );
                    $resultCount++;
                }
            }
        }

        // 3) Frais de scolarité fictifs : le 1er élève est soldé, le 2e partiel
        // et le 3e impayé, afin de tester le blocage des bulletins. Les suivants
        // sont soldés. Les montants sont indicatifs (FCFA).
        $due = 100000;
        $paidByIndex = [0 => $due, 1 => 40000, 2 => 0];
        foreach ($students as $index => $student) {
            $paid = $paidByIndex[$index] ?? $due;
            StudentContribution::withoutGlobalScopes()->updateOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $academicYear->id],
                [
                    'school_id' => $school->id,
                    'amount_due' => $due,
                    'amount_paid' => $paid,
                    'status' => $paid >= $due ? 'paid' : ($paid > 0 ? 'partial' : 'pending'),
                    'paid_at' => $paid >= $due ? now() : null,
                ],
            );
        }

        $this->command?->info(sprintf(
            'Notes fictives générées pour %s (%s) : %d matière(s), %d évaluation(s), %d note(s) — période %s %s.',
            $classRoom->name,
            $school->name,
            $subjects->count(),
            $evaluationCount,
            $resultCount,
            $academicYear->name,
            $period->name,
        ));
    }

    private function resolveTeacher(ClassRoom $classRoom, Subject $subject, $teachers, int $index): ?Teacher
    {
        $assignment = TeacherClassSubject::where('class_id', $classRoom->id)
            ->where('subject_id', $subject->id)
            ->first();

        if ($assignment) {
            return $teachers->firstWhere('id', $assignment->teacher_id);
        }

        $teacher = $teachers->isNotEmpty() ? $teachers[$index % $teachers->count()] : null;

        if ($teacher) {
            TeacherClassSubject::firstOrCreate([
                'teacher_id' => $teacher->id,
                'class_id' => $classRoom->id,
                'subject_id' => $subject->id,
            ]);
        }

        return $teacher;
    }
}
