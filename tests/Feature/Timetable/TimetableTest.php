<?php

namespace Tests\Feature\Timetable;

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Emploi du temps : vue d'administration, vue enseignant et vue parent.
 */
class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private ClassRoom $classRoom;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->year = AcademicYear::factory()->forSchool($this->school)->create();
        $this->classRoom = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => '6ème A',
        ]);
        $this->subject = Subject::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Mathématiques',
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->admin()->forSchool($this->school)->create();

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Enseignant dont le compte utilisateur appartient à l'école courante.
     */
    private function teacher(): Teacher
    {
        $user = User::factory()->teacher()->forSchool($this->school)->create();

        return Teacher::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => null,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:55',
            'room' => 'Salle 1',
        ], $overrides);
    }

    public function test_the_admin_page_lists_the_current_academic_year_slots(): void
    {
        $this->admin();

        Timetable::factory()->forSchool($this->school)->create([
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 2,
            'starts_at' => '10:00',
            'ends_at' => '11:55',
        ]);

        $this->get(route('admin.timetables.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Timetables/Index')
                ->has('slots', 1)
                ->has('days', 6)
                ->where('days.0.value', 1)
                ->where('days.0.label', 'Lundi')
                ->where('days.5.label', 'Samedi')
                ->has('academicYears')
                ->where('selectedAcademicYearId', $this->year->id)
                ->has('classes')
                ->has('subjects')
                ->has('teachers')
                ->where('canManage', true)
                ->where('slots.0.day_label', 'Mardi')
                ->where('slots.0.time_range', '10:00 – 11:55')
                ->where('slots.0.class_name', '6ème A')
                ->where('slots.0.subject_name', 'Mathématiques')
            );
    }

    public function test_an_admin_can_create_a_slot(): void
    {
        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload([
            'room' => 'Salle 4',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('timetables', [
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
            'room' => 'Salle 4',
        ]);
    }

    public function test_an_admin_can_update_and_delete_a_slot(): void
    {
        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $slot = Timetable::where('school_id', $this->school->id)->firstOrFail();

        $this->put(route('admin.timetables.update', $slot), $this->payload([
            'room' => 'Salle 9',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Salle 9', $slot->fresh()->room);

        $this->delete(route('admin.timetables.destroy', $slot))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('timetables', ['id' => $slot->id]);
    }

    public function test_the_end_time_must_be_after_the_start_time(): void
    {
        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload([
            'starts_at' => '10:00',
            'ends_at' => '09:00',
        ]))->assertSessionHasErrors('ends_at');

        $this->assertSame(0, Timetable::where('school_id', $this->school->id)->count());
    }

    public function test_a_class_cannot_have_two_courses_at_the_same_time(): void
    {
        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->post(route('admin.timetables.store'), $this->payload([
            'subject_id' => Subject::factory()->create([
                'school_id' => $this->school->id,
            ])->id,
            'room' => 'Salle 2',
        ]))->assertSessionHasErrors('class_id');
    }

    public function test_a_teacher_cannot_have_two_courses_at_the_same_time(): void
    {
        $teacher = $this->teacher();

        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload([
            'teacher_id' => $teacher->id,
        ]))->assertSessionHasNoErrors();

        $this->post(route('admin.timetables.store'), $this->payload([
            'teacher_id' => $teacher->id,
            'class_id' => ClassRoom::factory()->create([
                'school_id' => $this->school->id,
            ])->id,
            'room' => 'Salle 3',
        ]))->assertSessionHasErrors('teacher_id');
    }

    public function test_a_room_cannot_be_booked_twice_at_the_same_time(): void
    {
        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->post(route('admin.timetables.store'), $this->payload([
            'class_id' => ClassRoom::factory()->create([
                'school_id' => $this->school->id,
            ])->id,
        ]))->assertSessionHasErrors('room');
    }

    public function test_the_same_slot_may_exist_on_another_day(): void
    {
        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->post(route('admin.timetables.store'), $this->payload([
            'day_of_week' => 3,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, Timetable::where('school_id', $this->school->id)->count());
    }

    public function test_a_slot_of_another_school_cannot_be_referenced(): void
    {
        $this->admin();

        $foreignClass = ClassRoom::factory()->create();

        $this->post(route('admin.timetables.store'), $this->payload([
            'class_id' => $foreignClass->id,
        ]))->assertSessionHasErrors('class_id');

        $this->assertSame(0, Timetable::where('school_id', $this->school->id)->count());
    }

    public function test_a_school_id_sent_by_the_client_is_ignored(): void
    {
        $this->admin();

        $foreign = School::factory()->create();

        $this->post(route('admin.timetables.store'), $this->payload([
            'school_id' => $foreign->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(1, Timetable::where('school_id', $this->school->id)->count());
        $this->assertSame(0, Timetable::where('school_id', $foreign->id)->count());
    }

    public function test_an_admin_of_another_school_cannot_touch_this_slot(): void
    {
        $this->admin();

        $this->post(route('admin.timetables.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $slot = Timetable::where('school_id', $this->school->id)->firstOrFail();

        $this->actingAs(
            User::factory()->admin()->forSchool(School::factory()->create())->create()
        );

        $this->put(route('admin.timetables.update', $slot), $this->payload([
            'room' => 'Piraté',
        ]))->assertNotFound();

        $this->delete(route('admin.timetables.destroy', $slot))->assertNotFound();

        $this->assertSame('Salle 1', $slot->fresh()->room);
    }

    public function test_a_teacher_only_sees_their_own_slots(): void
    {
        $teacher = $this->teacher();
        $colleague = $this->teacher();

        Timetable::factory()->forSchool($this->school)->create([
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'teacher_id' => $teacher->id,
        ]);

        Timetable::factory()->forSchool($this->school)->create([
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 4,
            'starts_at' => '14:00',
            'ends_at' => '15:00',
            'teacher_id' => $colleague->id,
        ]);

        $this->actingAs($teacher->user)
            ->get(route('teacher.timetable'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teacher/Timetable')
                ->has('slots', 1)
                ->where('slots.0.day_label', 'Lundi')
                ->where('slots.0.subject_name', 'Mathématiques')
            );
    }

    public function test_a_teacher_cannot_read_a_colleague_timetable_by_forcing_a_parameter(): void
    {
        $colleague = $this->teacher();

        Timetable::factory()->forSchool($this->school)->create([
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $colleague->id,
        ]);

        $mine = $this->teacher();

        // Le paramètre teacher_id reçu dans l'URL est ignoré : le filtre est le compte connecté.
        $this->actingAs($mine->user)
            ->get(route('teacher.timetable', ['teacher_id' => $colleague->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('slots', 0));
    }

    public function test_a_parent_sees_the_timetable_of_their_own_child(): void
    {
        $student = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
            'first_name' => 'Chantal',
            'last_name' => 'Ahouandjinou',
        ]);

        $parentUser = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Parent,
        ]);

        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'user_id' => $parentUser->id,
        ]);

        $guardian->students()->attach($student);

        Timetable::factory()->forSchool($this->school)->create([
            'academic_year_id' => $this->year->id,
            'class_id' => $this->classRoom->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 5,
            'starts_at' => '15:00',
            'ends_at' => '16:55',
        ]);

        $this->actingAs($parentUser)
            ->get(route('parent.children.timetable', $student))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Parent/Timetable')
                ->has('student')
                ->where('student.name', 'Chantal Ahouandjinou')
                ->where('student.class_name', '6ème A')
                ->has('slots', 1)
                ->where('slots.0.day_label', 'Vendredi')
            );
    }

    public function test_a_parent_cannot_open_another_child_of_the_same_school(): void
    {
        $ownChild = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
        ]);

        $otherChild = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->classRoom->id,
        ]);

        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
        ]);

        $guardian->students()->attach($ownChild);

        $this->actingAs(User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Parent,
        ]))
            ->get(route('parent.children.timetable', $otherChild))
            ->assertForbidden();
    }

    public function test_a_parent_cannot_open_a_child_of_another_school(): void
    {
        $foreignChild = Student::factory()->create([
            'class_id' => $this->classRoom->id,
        ]);

        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
        ]);

        $this->actingAs(User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Parent,
        ]))
            ->get(route('parent.children.timetable', $foreignChild))
            ->assertNotFound();
    }

    public function test_a_teacher_cannot_reach_the_admin_timetable(): void
    {
        $this->actingAs(
            User::factory()->teacher()->forSchool($this->school)->create()
        )
            ->get(route('admin.timetables.index'))
            ->assertForbidden();
    }

    public function test_the_censeur_sees_the_timetable_without_being_able_to_manage_it(): void
    {
        $this->actingAs(
            User::factory()->create([
                'school_id' => $this->school->id,
                'role' => Role::Censeur,
            ])
        )
            ->get(route('censeur.timetables.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Timetables/Index')
                ->where('canManage', false)
            );
    }
}
