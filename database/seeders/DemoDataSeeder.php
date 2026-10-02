<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\StudentContribution;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Peuple l'école principale (Lycée d'Excellence) avec des données réalistes :
 * année scolaire, classes, matières, enseignants affectés, élèves, parents,
 * évaluations (dont une validée qui déclenche de vraies notifications SMS,
 * ici journalisées par LogSmsProvider).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('slug', 'lycee-excellence')->firstOrFail();

        $year = AcademicYear::withoutGlobalScopes()->updateOrCreate(
            ['school_id' => $school->id, 'name' => now()->year.'-'.(now()->year + 1)],
            ['starts_on' => now()->startOfYear(), 'ends_on' => now()->endOfYear(), 'is_current' => true],
        );

        $classNames = ['6ème A', '5ème A', '4ème A', '3ème A', '3ème B'];
        $classes = collect($classNames)->map(fn ($name) => ClassRoom::withoutGlobalScopes()->updateOrCreate(
            ['school_id' => $school->id, 'name' => $name],
            ['capacity' => 40],
        ));

        $subjectNames = ['Mathématiques', 'Français', 'Anglais', 'SVT', 'Physique-Chimie', 'Histoire-Géographie', 'EPS', 'Informatique', 'Philosophie', 'Espagnol'];
        $subjects = collect($subjectNames)->map(fn ($name) => Subject::withoutGlobalScopes()->updateOrCreate(
            ['school_id' => $school->id, 'name' => $name],
        ));

        foreach ($classes as $class) {
            $class->subjects()->syncWithoutDetaching($subjects->pluck('id'));
        }

        $teacherNames = ['Marie Houngbo', 'Jean Adjahouinou', 'Fatima Sanni', 'Koffi Mensah', 'Aïcha Boni'];
        $teachers = collect($teacherNames)->map(function (string $name, int $i) use ($school) {
            $email = 'prof'.($i + 1).'@lycee-excellence.test';
            $user = User::withoutGlobalScopes()->firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'school_id' => $school->id,
                'role' => 'teacher',
                'is_active' => true,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ])->save();

            return Teacher::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $user->id],
                ['school_id' => $school->id, 'phone' => '+229 01 97 00 0'.($i + 1).'0'],
            );
        });

        // Chaque enseignant est affecté à 2 matières, chacune dans 2 classes.
        foreach ($teachers as $i => $teacher) {
            $teacherSubjects = $subjects->slice($i * 2 % $subjects->count(), 2)->values();
            $teacherClasses = $classes->slice($i % $classes->count(), 2)->values();

            foreach ($teacherSubjects as $subject) {
                foreach ($teacherClasses as $class) {
                    $teacher->assignments()->firstOrCreate([
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                    ]);
                }
            }
        }

        // 100 élèves répartis dans les 5 classes, chacun avec un parent.
        $students = collect();
        for ($i = 1; $i <= 100; $i++) {
            $class = $classes[($i - 1) % $classes->count()];

            $student = Student::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'matricule' => sprintf('LEC-%04d', $i)],
                [
                    'class_id' => $class->id,
                    'first_name' => 'Élève'.$i,
                    'last_name' => 'Famille'.$i,
                    'birth_date' => now()->subYears(11)->subDays($i),
                    'gender' => $i % 2 === 0 ? 'F' : 'M',
                    'is_active' => true,
                ],
            );

            $guardian = ParentGuardian::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'phone' => '+229 90 00 '.str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                ['name' => 'Parent de '.$student->first_name],
            );
            $guardian->students()->syncWithoutDetaching([$student->id => ['relationship' => 'parent']]);

            $students->push($student);

            StudentContribution::withoutGlobalScopes()->updateOrCreate(['student_id'=>$student->id,'academic_year_id'=>$year->id], ['school_id'=>$school->id,'parent_id'=>$guardian->id,'amount_due'=>1000,'amount_paid'=>1000,'status'=>'paid','paid_at'=>now()]);
        }

        // Une évaluation par binôme classe/matière affecté, en brouillon.
        $firstTeacher = $teachers->first();
        foreach ($firstTeacher->assignments()->with(['classRoom', 'subject'])->get() as $assignment) {
            $evaluation = Evaluation::withoutGlobalScopes()->firstOrCreate(
                [
                    'school_id' => $school->id,
                    'class_id' => $assignment->class_id,
                    'subject_id' => $assignment->subject_id,
                    'teacher_id' => $firstTeacher->id,
                    'title' => 'Devoir 1',
                ],
                [
                    'academic_year_id' => $year->id,
                    'type' => 'devoir',
                    'evaluation_date' => now()->subDays(3),
                    'max_score' => 20,
                    'coefficient' => 2,
                    'status' => 'draft',
                    'created_by' => $firstTeacher->user_id,
                ],
            );

            foreach (Student::withoutGlobalScopes()->where('class_id', $assignment->class_id)->get() as $student) {
                $evaluation->results()->firstOrCreate(
                    ['evaluation_id' => $evaluation->id, 'student_id' => $student->id],
                    ['school_id' => $school->id],
                );
            }
        }

        // Une évaluation validée, avec notes et notifications déjà envoyées (via le provider "log").
        $target = $firstTeacher->assignments()->first();
        if ($target) {
            $evaluation = Evaluation::withoutGlobalScopes()->firstOrCreate(
                [
                    'school_id' => $school->id,
                    'class_id' => $target->class_id,
                    'subject_id' => $target->subject_id,
                    'teacher_id' => $firstTeacher->id,
                    'title' => 'Interrogation 1',
                ],
                [
                    'academic_year_id' => $year->id,
                    'type' => 'interrogation',
                    'evaluation_date' => now()->subDays(10),
                    'max_score' => 20,
                    'coefficient' => 1,
                    'status' => 'draft',
                    'created_by' => $firstTeacher->user_id,
                ],
            );

            $classStudents = Student::withoutGlobalScopes()->where('class_id', $target->class_id)->get();
            foreach ($classStudents as $student) {
                $evaluation->results()->updateOrCreate(
                    ['evaluation_id' => $evaluation->id, 'student_id' => $student->id],
                    ['school_id' => $school->id, 'score' => rand(8, 20), 'updated_by' => $firstTeacher->user_id],
                );
            }

            if (! $evaluation->isValidated()) {
                $evaluation->update(['status' => 'validated', 'validated_at' => now(), 'validated_by' => $firstTeacher->user_id]);
                app(NotificationService::class)->notifyForEvaluation($evaluation->fresh());
            }
        }
    }
}
