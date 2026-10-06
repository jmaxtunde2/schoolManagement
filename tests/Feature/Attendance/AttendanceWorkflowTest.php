<?php

namespace Tests\Feature\Attendance;

use App\Enums\AttendanceJustificationStatus;
use App\Enums\AttendanceStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceJustification;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AttendanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private ClassRoom $classRoom;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);
        $this->classRoom = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
        ]);
        $subject = Subject::factory()->create(['school_id' => $this->school->id]);
        $teacherUser = User::factory()->teacher()->forSchool($this->school)->create();
        $this->teacher = Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $teacherUser->id,
        ]);
        $this->teacher->assignments()->create([
            'class_id' => $this->classRoom->id,
            'subject_id' => $subject->id,
        ]);
    }

    private function actingAsVerified(User $user): static
    {
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ])->actingAs($user);
    }

    private function student(string $firstName, ?ClassRoom $classRoom = null): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => ($classRoom ?? $this->classRoom)->id,
            'first_name' => $firstName,
            'is_active' => true,
        ]);
    }

    public function test_assigned_teacher_records_daily_absence_and_late_with_audit(): void
    {
        $absent = $this->student('Awa');
        $late = $this->student('Kofi');
        $date = now()->toDateString();
        $teacherUser = $this->teacher->user;

        $this->actingAsVerified($teacherUser)
            ->get('/teacher/attendance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Attendance/Index')
                ->where('routePrefix', 'teacher')
                ->where('stats.absent_today', 0));

        $this->actingAsVerified($teacherUser)
            ->get("/teacher/attendance/create?class_id={$this->classRoom->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Attendance/Create')
                ->where('selectedClass.id', $this->classRoom->id)
                ->has('students', 2));

        $this->actingAsVerified($teacherUser)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => $date,
                'records' => [
                    ['student_id' => $absent->id, 'status' => 'absent', 'reason' => 'Maladie'],
                    ['student_id' => $late->id, 'status' => 'late', 'delay_minutes' => 12],
                ],
            ])
            ->assertRedirect(route('teacher.attendance.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertDatabaseHas('attendance_records', [
            'school_id' => $this->school->id,
            'student_id' => $absent->id,
            'class_room_id' => $this->classRoom->id,
            'status' => AttendanceStatus::Absent->value,
            'reason' => 'Maladie',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $late->id,
            'status' => AttendanceStatus::Late->value,
            'delay_minutes' => 12,
        ]);
        $this->assertSame(2, AuditLog::where('action', 'attendance.created')->count());

        $this->actingAsVerified($teacherUser)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => $date,
                'records' => [
                    ['student_id' => $absent->id, 'status' => 'present'],
                    ['student_id' => $late->id, 'status' => 'late', 'delay_minutes' => 5],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('attendance_records', 2);
        $this->assertSame(AttendanceStatus::Present, AttendanceRecord::withoutGlobalScopes()->where('student_id', $absent->id)->firstOrFail()->status);
        $this->assertSame(5, AttendanceRecord::withoutGlobalScopes()->where('student_id', $late->id)->value('delay_minutes'));
        $this->assertSame(2, AuditLog::where('action', 'attendance.updated')->count());

        $this->actingAsVerified($teacherUser)
            ->get("/teacher/attendance/students/{$absent->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Attendance/Student')
                ->where('student.id', $absent->id)
                ->where('stats.absent', 0));
    }

    public function test_teacher_cannot_take_attendance_for_an_unassigned_class(): void
    {
        $unassigned = ClassRoom::factory()->create(['school_id' => $this->school->id]);
        $student = $this->student('Ama', $unassigned);

        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance', [
                'class_room_id' => $unassigned->id,
                'attendance_date' => now()->toDateString(),
                'records' => [['student_id' => $student->id, 'status' => 'absent']],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('attendance_records', 0);
    }

    public function test_admin_attendance_list_and_statistics_are_school_scoped(): void
    {
        $studentA = $this->student('Afi');
        $recordA = AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $studentA->id,
            'class_room_id' => $this->classRoom->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::Absent,
        ]);

        $schoolB = School::factory()->create();
        $classB = ClassRoom::factory()->create(['school_id' => $schoolB->id]);
        $studentB = Student::factory()->create([
            'school_id' => $schoolB->id,
            'class_id' => $classB->id,
        ]);
        $recordB = AttendanceRecord::withoutGlobalScopes()->create([
            'school_id' => $schoolB->id,
            'student_id' => $studentB->id,
            'class_room_id' => $classB->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::Absent,
        ]);
        $admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->actingAsVerified($admin)
            ->get('/admin/attendance')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Staff/Attendance/Index')
                ->where('records.total', 1)
                ->where('stats.absent_today', 1)
                ->where('records.data.0.id', $recordA->id));

        $this->actingAsVerified($admin)
            ->put("/admin/attendance/{$recordB->id}", [
                'status' => 'present',
            ])
            ->assertNotFound();

        $this->assertSame(AttendanceStatus::Absent, $recordB->fresh()->status);
    }

    public function test_negative_delay_is_rejected_and_parent_only_sees_own_child(): void
    {
        $child = $this->student('Mina');
        $otherChild = $this->student('Esi');
        $record = AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $child->id,
            'class_room_id' => $this->classRoom->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::Absent,
        ]);
        $guardianUser = User::factory()->forSchool($this->school)->create(['role' => 'parent']);
        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $guardianUser->id,
        ]);
        $guardian->students()->attach($child->id, ['relationship' => 'parent']);

        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => now()->toDateString(),
                'records' => [['student_id' => $child->id, 'status' => 'late', 'delay_minutes' => -1]],
            ])
            ->assertSessionHasErrors('records.0.delay_minutes');

        $this->actingAsVerified($guardianUser)
            ->get("/parent/children/{$child->id}/attendance")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Attendance')
                ->where('student.id', $child->id)
                ->where('stats.absent', 1));

        $this->actingAsVerified($guardianUser)
            ->get("/parent/children/{$otherChild->id}/attendance")
            ->assertForbidden();

        $this->assertSame(AttendanceStatus::Absent, $record->fresh()->status);
    }

    public function test_parent_can_request_absence_justification_and_staff_can_approve_it(): void
    {
        $student = $this->student('Noah');
        $record = AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'attendance_date' => now()->subDay()->toDateString(),
            'status' => AttendanceStatus::Absent,
        ]);
        $guardianUser = User::factory()->forSchool($this->school)->create(['role' => 'parent']);
        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $guardianUser->id,
        ]);
        $guardian->students()->attach($student->id, ['relationship' => 'parent']);

        $this->actingAsVerified($guardianUser)
            ->post("/parent/attendance/{$record->id}/justification", [
                'reason' => 'Consultation médicale.',
            ])
            ->assertSessionHasNoErrors();

        $justification = AttendanceJustification::firstOrFail();
        $this->assertSame(AttendanceJustificationStatus::Pending, $justification->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.justification_submitted']);

        $censeur = User::factory()->forSchool($this->school)->create(['role' => 'censeur']);
        $this->actingAsVerified($censeur)
            ->post("/censeur/attendance/{$record->id}/justify", [
                'justification_id' => $justification->id,
                'status' => 'approved',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(AttendanceJustificationStatus::Approved, $justification->fresh()->status);
        $this->assertNotNull($record->fresh()->justified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.justified']);

        $this->actingAsVerified($guardianUser)
            ->post("/parent/attendance/{$record->id}/justification", [
                'reason' => 'Nouvelle demande après approbation.',
            ])
            ->assertForbidden();

        $this->actingAsVerified($guardianUser)
            ->put("/parent/attendance/{$record->id}", ['status' => 'present'])
            ->assertNotFound();
    }
}
