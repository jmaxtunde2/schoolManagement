<?php

namespace Tests\Feature\Academic;

use App\Enums\EvaluationStatus;
use App\Enums\EvaluationType;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Evaluation;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academic\AcademicCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use Tests\TestCase;

class AcademicCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_validated_non_absent_scores_are_averaged_and_ranked(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'is_current' => true,
        ]);
        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
        ]);
        $classRoom = ClassRoom::factory()->create(['school_id' => $school->id]);
        $math = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathématiques']);
        $french = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Français']);
        $teacherUser = User::factory()->teacher()->forSchool($school)->create();
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
        ]);
        $classRoom->subjects()->sync([
            $math->id => ['default_coefficient' => 4],
            $french->id => ['default_coefficient' => 2],
        ]);

        $topStudent = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
            'first_name' => 'Awa',
        ]);
        $tiedStudent = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
            'first_name' => 'Kofi',
        ]);
        $thirdStudent = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
            'first_name' => 'Mina',
        ]);
        $noGradeStudent = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
            'first_name' => 'Noah',
        ]);

        $mathOne = $this->evaluation($school, $year, $period, $classRoom, $math, $teacher, 20, 2);
        $mathTwo = $this->evaluation($school, $year, $period, $classRoom, $math, $teacher, 10, 1);
        $frenchOne = $this->evaluation($school, $year, $period, $classRoom, $french, $teacher, 20, 1);
        $draft = $this->evaluation($school, $year, $period, $classRoom, $math, $teacher, 20, 1, EvaluationStatus::Draft);
        $otherPeriod = AcademicPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 2',
            'position' => 2,
            'starts_at' => now()->addYear()->startOfYear()->toDateString(),
            'ends_at' => now()->addYear()->endOfYear()->toDateString(),
        ]);
        $nextPeriodEval = $this->evaluation($school, $year, $otherPeriod, $classRoom, $math, $teacher, 20, 1);

        $this->score($mathOne, $topStudent, 14);
        $this->score($mathTwo, $topStudent, 10);
        $this->score($frenchOne, $topStudent, 12);
        $this->score($draft, $topStudent, 20);
        $this->score($nextPeriodEval, $topStudent, 20);
        $this->score($mathOne, $tiedStudent, 14);
        $this->score($mathTwo, $tiedStudent, 10);
        $this->score($frenchOne, $tiedStudent, 12);
        $this->score($mathOne, $thirdStudent, 12);
        $this->score($frenchOne, $thirdStudent, 12);
        $this->score($mathOne, $noGradeStudent, null, true);

        $this->actingAs(User::factory()->admin()->forSchool($school)->create());
        $result = app(AcademicCalculationService::class)->calculateClassResults($classRoom, $period, (int) $school->id);
        $byStudent = collect($result['students'])->keyBy('student_id');

        $mathAverage = collect($byStudent[$topStudent->id]['subjects'])
            ->firstWhere('subject_name', 'Mathématiques')['average'];
        $this->assertSame(17.0, $mathAverage);
        $this->assertSame(15.33, $byStudent[$topStudent->id]['general_average']);
        $this->assertSame(3, $byStudent[$topStudent->id]['evaluation_count']);
        $this->assertSame(1, $byStudent[$topStudent->id]['rank']);
        $this->assertSame(1, $byStudent[$tiedStudent->id]['rank']);
        $this->assertSame(3, $byStudent[$thirdStudent->id]['rank']);
        $this->assertNull($byStudent[$noGradeStudent->id]['general_average']);
        $this->assertNull($byStudent[$noGradeStudent->id]['rank']);
    }

    public function test_subject_average_groups_interrogations_and_counts_each_devoir(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'is_current' => true,
        ]);
        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
        ]);
        $classRoom = ClassRoom::factory()->create(['school_id' => $school->id]);
        $math = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Mathématiques']);
        $teacherUser = User::factory()->teacher()->forSchool($school)->create();
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
        ]);
        $classRoom->subjects()->sync([
            $math->id => ['default_coefficient' => 3],
        ]);

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
        ]);

        // Moyenne interros = (10 + 20) / 2 = 15 ; puis (15 + devoir 1 + devoir 2) / 3.
        $this->score($this->typedEvaluation($school, $year, $period, $classRoom, $math, $teacher, EvaluationType::Interrogation, 20), $student, 10);
        $this->score($this->typedEvaluation($school, $year, $period, $classRoom, $math, $teacher, EvaluationType::Interrogation, 20), $student, 20);
        $this->score($this->typedEvaluation($school, $year, $period, $classRoom, $math, $teacher, EvaluationType::Devoir, 20), $student, 15);
        $this->score($this->typedEvaluation($school, $year, $period, $classRoom, $math, $teacher, EvaluationType::Devoir, 20), $student, 5);

        $result = app(AcademicCalculationService::class)->calculateClassResults($classRoom, $period, (int) $school->id);
        $row = collect($result['students'])->firstWhere('student_id', $student->id);

        $this->assertSame(11.67, collect($row['subjects'])->firstWhere('subject_name', 'Mathématiques')['average']);
        $this->assertSame(11.67, $row['general_average']);
        $this->assertSame(4, $row['evaluation_count']);
    }

    public function test_subject_average_uses_only_interrogations_when_no_devoir_exists(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create([
            'school_id' => $school->id,
            'is_current' => true,
        ]);
        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
        ]);
        $classRoom = ClassRoom::factory()->create(['school_id' => $school->id]);
        $english = Subject::factory()->create(['school_id' => $school->id, 'name' => 'Anglais']);
        $teacherUser = User::factory()->teacher()->forSchool($school)->create();
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
        ]);
        $classRoom->subjects()->sync([
            $english->id => ['default_coefficient' => 2],
        ]);

        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $classRoom->id,
        ]);

        $this->score($this->typedEvaluation($school, $year, $period, $classRoom, $english, $teacher, EvaluationType::Interrogation, 20), $student, 8);
        $this->score($this->typedEvaluation($school, $year, $period, $classRoom, $english, $teacher, EvaluationType::Interrogation, 20), $student, 12);

        $result = app(AcademicCalculationService::class)->calculateClassResults($classRoom, $period, (int) $school->id);
        $row = collect($result['students'])->firstWhere('student_id', $student->id);

        $this->assertSame(10.0, collect($row['subjects'])->firstWhere('subject_name', 'Anglais')['average']);
        $this->assertSame(10.0, $row['general_average']);
    }

    public function test_teacher_results_are_limited_to_assigned_classes(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $period = AcademicPeriod::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => 'Période 1',
            'position' => 1,
        ]);
        $assignedClass = ClassRoom::factory()->create(['school_id' => $school->id]);
        $unassignedClass = ClassRoom::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);
        $teacherUser = User::factory()->teacher()->forSchool($school)->create([
            'two_factor_confirmed_at' => now(),
        ]);
        $teacher = Teacher::factory()->create([
            'school_id' => $school->id,
            'user_id' => $teacherUser->id,
        ]);
        $teacher->assignments()->create([
            'class_id' => $assignedClass->id,
            'subject_id' => $subject->id,
        ]);

        $this->withSession(['two_factor_verified_at' => now()->timestamp])
            ->actingAs($teacherUser)
            ->get('/teacher/academic/results')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Academic/Results')
                ->has('classes', 1)
                ->where('classes.0.id', $assignedClass->id));

        $this->withSession(['two_factor_verified_at' => now()->timestamp])
            ->actingAs($teacherUser)
            ->get('/teacher/academic/results?class_id='.$unassignedClass->id.'&period_id='.$period->id)
            ->assertForbidden();
    }

    public function test_calculation_service_rejects_a_foreign_school_class(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $yearB = AcademicYear::factory()->create(['school_id' => $schoolB->id]);
        $periodB = AcademicPeriod::create([
            'school_id' => $schoolB->id,
            'academic_year_id' => $yearB->id,
            'name' => 'Période B',
            'position' => 1,
        ]);
        $classB = ClassRoom::factory()->create(['school_id' => $schoolB->id]);
        $this->actingAs(User::factory()->admin()->forSchool($schoolA)->create());

        $this->expectException(InvalidArgumentException::class);
        app(AcademicCalculationService::class)->calculateClassResults($classB, $periodB, (int) $schoolA->id);
    }

    private function evaluation(
        School $school,
        AcademicYear $year,
        AcademicPeriod $period,
        ClassRoom $classRoom,
        Subject $subject,
        Teacher $teacher,
        float $maxScore,
        float $coefficient,
        EvaluationStatus $status = EvaluationStatus::Validated
    ): Evaluation {
        return Evaluation::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'academic_period_id' => $period->id,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'max_score' => $maxScore,
            'coefficient' => $coefficient,
            'status' => $status->value,
            'validated_at' => $status === EvaluationStatus::Validated ? now() : null,
        ]);
    }

    private function score(Evaluation $evaluation, Student $student, ?float $score, bool $absent = false): void
    {
        $evaluation->results()->create([
            'school_id' => $evaluation->school_id,
            'student_id' => $student->id,
            'score' => $score,
            'is_absent' => $absent,
        ]);
    }

    private function typedEvaluation(
        School $school,
        AcademicYear $year,
        AcademicPeriod $period,
        ClassRoom $classRoom,
        Subject $subject,
        Teacher $teacher,
        EvaluationType $type,
        float $maxScore,
    ): Evaluation {
        return Evaluation::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'academic_period_id' => $period->id,
            'class_id' => $classRoom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'type' => $type->value,
            'max_score' => $maxScore,
            'coefficient' => 1,
            'status' => EvaluationStatus::Validated->value,
            'validated_at' => now(),
        ]);
    }
}
