<?php

namespace Tests\Feature\Attendance;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceValidationStatus;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ClassRoom;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AttendanceValidationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private ClassRoom $classRoom;

    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

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

    private function student(string $firstName): Student
    {
        return Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
            'first_name' => $firstName,
            'is_active' => true,
        ]);
    }

    private function guardianFor(Student $student): User
    {
        $guardianUser = User::factory()->forSchool($this->school)->create(['role' => 'parent']);
        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $guardianUser->id,
        ]);
        $guardian->students()->attach($student->id, ['relationship' => 'parent']);

        return $guardianUser;
    }

    public function test_recorded_attendance_starts_pending_and_notifies_no_parent(): void
    {
        $student = $this->student('Awa');
        $guardianUser = $this->guardianFor($student);

        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => now()->toDateString(),
                'records' => [['student_id' => $student->id, 'status' => 'absent', 'reason' => 'Maladie']],
            ])
            ->assertSessionHasNoErrors();

        $record = AttendanceRecord::withoutGlobalScopes()->where('student_id', $student->id)->firstOrFail();
        $this->assertSame(AttendanceValidationStatus::Pending, $record->validation_status);
        $this->assertNull($record->validated_at);
        $this->assertNull($record->validated_by);
        $this->assertSame(0, UserNotification::where('user_id', $guardianUser->id)->count());

        $this->actingAsVerified($this->teacher->user)
            ->get('/teacher/attendance')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Staff/Attendance/Index')
                ->where('records.data.0.validation_status', 'pending')
                ->where('records.data.0.can_validate', false)
                ->where('stats.pending_validation', 1));
    }

    public function test_censeur_validates_record_and_notifies_guardians(): void
    {
        $student = $this->student('Kofi');
        $guardianUser = $this->guardianFor($student);
        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => now()->toDateString(),
                'records' => [['student_id' => $student->id, 'status' => 'absent']],
            ])
            ->assertSessionHasNoErrors();

        $record = AttendanceRecord::withoutGlobalScopes()->where('student_id', $student->id)->firstOrFail();
        $censeur = User::factory()->forSchool($this->school)->create(['role' => 'censeur']);

        $this->actingAsVerified($censeur)
            ->post('/censeur/attendance/validate', ['record_ids' => [$record->id]])
            ->assertSessionHasNoErrors();

        $fresh = $record->fresh();
        $this->assertSame(AttendanceValidationStatus::Validated, $fresh->validation_status);
        $this->assertNotNull($fresh->validated_at);
        $this->assertSame($censeur->id, $fresh->validated_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'attendance.validated']);

        $notification = UserNotification::where('user_id', $guardianUser->id)
            ->where('type', 'attendance.validated')
            ->firstOrFail();
        $this->assertSame('Absence signalée', $notification->title);
        $this->assertSame($record->id, $notification->data['attendance_record_id']);

        // Re-valider un relevé déjà validé ne renvoie pas de notification.
        $this->actingAsVerified($censeur)
            ->post('/censeur/attendance/validate', ['record_ids' => [$record->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, UserNotification::where('user_id', $guardianUser->id)
            ->where('type', 'attendance.validated')->count());
    }

    public function test_teacher_and_secretary_cannot_validate(): void
    {
        $student = $this->student('Esi');
        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => now()->toDateString(),
                'records' => [['student_id' => $student->id, 'status' => 'absent']],
            ])
            ->assertSessionHasNoErrors();

        $record = AttendanceRecord::withoutGlobalScopes()->where('student_id', $student->id)->firstOrFail();

        // Aucune route de validation n'est ouverte aux enseignants / secrétaires.
        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance/validate', ['record_ids' => [$record->id]])
            ->assertStatus(405);

        $secretary = User::factory()->forSchool($this->school)->create(['role' => 'secretary']);
        $this->actingAsVerified($secretary)
            ->post('/teacher/attendance/validate', ['record_ids' => [$record->id]])
            ->assertStatus(405);

        // Même avec une route d'un autre préfixe, le rôle est refusé.
        $this->actingAsVerified($this->teacher->user)
            ->post('/surveillant/attendance/validate', ['record_ids' => [$record->id]])
            ->assertForbidden();

        $this->assertSame(AttendanceValidationStatus::Pending, $record->fresh()->validation_status);
    }

    public function test_surveillant_can_validate_and_editing_a_validated_record_requires_new_validation(): void
    {
        $student = $this->student('Yao');
        $date = now()->toDateString();
        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => $date,
                'records' => [['student_id' => $student->id, 'status' => 'late', 'delay_minutes' => 10]],
            ])
            ->assertSessionHasNoErrors();

        $record = AttendanceRecord::withoutGlobalScopes()->where('student_id', $student->id)->firstOrFail();
        $surveillant = User::factory()->forSchool($this->school)->create(['role' => 'surveillant']);

        $this->actingAsVerified($surveillant)
            ->post('/surveillant/attendance/validate', ['record_ids' => [$record->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame(AttendanceValidationStatus::Validated, $record->fresh()->validation_status);

        $this->actingAsVerified($surveillant)
            ->put("/surveillant/attendance/{$record->id}", ['status' => 'late', 'delay_minutes' => 25])
            ->assertSessionHasNoErrors();

        $fresh = $record->fresh();
        $this->assertSame(AttendanceValidationStatus::Pending, $fresh->validation_status);
        $this->assertNull($fresh->validated_at);
        $this->assertNull($fresh->validated_by);
    }

    public function test_bulk_validation_notifies_only_incident_students(): void
    {
        $absent = $this->student('Ama');
        $present = $this->student('Kojo');
        $absentGuardian = $this->guardianFor($absent);
        $presentGuardian = $this->guardianFor($present);
        $date = now()->toDateString();

        $this->actingAsVerified($this->teacher->user)
            ->post('/teacher/attendance', [
                'class_room_id' => $this->classRoom->id,
                'attendance_date' => $date,
                'records' => [
                    ['student_id' => $absent->id, 'status' => 'absent'],
                    ['student_id' => $present->id, 'status' => 'present'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $ids = AttendanceRecord::withoutGlobalScopes()
            ->where('school_id', $this->school->id)
            ->pluck('id')
            ->all();

        $director = User::factory()->forSchool($this->school)->create(['role' => 'director']);

        $this->actingAsVerified($director)
            ->post('/director/attendance/validate', ['record_ids' => $ids])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame(
            0,
            AttendanceRecord::withoutGlobalScopes()
                ->where('school_id', $this->school->id)
                ->where('validation_status', AttendanceValidationStatus::Pending->value)
                ->count()
        );
        $this->assertSame(1, UserNotification::where('user_id', $absentGuardian->id)
            ->where('type', 'attendance.validated')->count());
        $this->assertSame(0, UserNotification::where('user_id', $presentGuardian->id)->count());
    }

    public function test_admin_can_validate_from_admin_prefix(): void
    {
        $student = $this->student('Ada');
        $record = AttendanceRecord::create([
            'school_id' => $this->school->id,
            'student_id' => $student->id,
            'class_room_id' => $this->classRoom->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::Absent,
        ]);
        $admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->actingAsVerified($admin)
            ->post('/admin/attendance/validate', ['record_ids' => [$record->id]])
            ->assertSessionHasNoErrors();

        $fresh = $record->fresh();
        $this->assertSame(AttendanceValidationStatus::Validated, $fresh->validation_status);
        $this->assertSame($admin->id, $fresh->validated_by);
    }

    public function test_absence_and_lateness_create_template_notifications_with_ordered_parameters(): void
    {
        config([
            'services.whatsapp.lateness_template_language' => 'fr',
            'services.whatsapp.token' => 'fake-token',
            'services.whatsapp.phone_number_id' => '123456',
        ]);
        $absent = $this->student('Ama');
        $late = $this->student('Kojo');
        $absentUser = $this->guardianFor($absent);
        $lateUser = $this->guardianFor($late);
        ParentGuardian::where('user_id', $absentUser->id)->update(['name' => 'Parent Ama', 'phone' => '+22890000001']);
        ParentGuardian::where('user_id', $lateUser->id)->update(['name' => 'Parent Kojo', 'phone' => '+22890000002']);
        $date = now()->toDateString();

        $this->actingAsVerified($this->teacher->user)->post('/teacher/attendance', [
            'class_room_id' => $this->classRoom->id,
            'attendance_date' => $date,
            'records' => [
                ['student_id' => $absent->id, 'status' => 'absent'],
                ['student_id' => $late->id, 'status' => 'late', 'delay_minutes' => 10],
            ],
        ])->assertSessionHasNoErrors();

        $records = AttendanceRecord::withoutGlobalScopes()->whereIn('student_id', [$absent->id, $late->id])->get();
        foreach ($records as $record) {
            $record->forceFill(['recorded_at' => now()->setTime(8, 15)])->save();
        }
        $director = User::factory()->forSchool($this->school)->create(['role' => 'director']);
        $this->actingAsVerified($director)->post('/director/attendance/validate', [
            'record_ids' => $records->pluck('id')->all(),
        ])->assertSessionHasNoErrors();

        $absenceNotification = Notification::where('notification_type', 'absence')->firstOrFail();
        $this->assertSame('corischool_absence', data_get($absenceNotification->metadata, 'whatsapp.template'));
        $this->assertSame([
            $this->school->name, 'Parent Ama', $absent->full_name,
            now()->isoFormat('dddd D MMMM YYYY'),
        ], data_get($absenceNotification->metadata, 'whatsapp.body_parameters'));
        $this->assertSame($this->school->slug, data_get($absenceNotification->metadata, 'whatsapp.url_parameter'));

        $latenessNotification = Notification::where('notification_type', 'lateness')->firstOrFail();
        $this->assertSame('template_retard', data_get($latenessNotification->metadata, 'whatsapp.template'));
        $this->assertSame([
            $this->school->name, 'Parent Kojo', $late->full_name,
            now()->isoFormat('dddd D MMMM YYYY'), '08:15',
        ], data_get($latenessNotification->metadata, 'whatsapp.body_parameters'));
        $this->assertSame('fr', data_get($latenessNotification->metadata, 'whatsapp.language'));
        $this->assertSame($this->school->slug, data_get($latenessNotification->metadata, 'whatsapp.url_parameter'));
    }
}
