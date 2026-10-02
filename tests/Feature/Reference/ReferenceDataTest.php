<?php

namespace Tests\Feature\Reference;

use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    private function admin(School $school): User
    {
        return User::factory()->admin()->forSchool($school)->create();
    }

    private function censeur(School $school): User
    {
        return User::factory()->forSchool($school)->create([
            'role' => 'censeur',
            'two_factor_confirmed_at' => now(),
        ]);
    }

    private function actingAsVerifiedCenseur(User $censeur): static
    {
        return $this->actingAs($censeur)->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ]);
    }

    public function test_admin_can_create_and_update_a_class(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin($school))
            ->post('/admin/classes', ['name' => '3ème A', 'capacity' => 35])
            ->assertSessionHasNoErrors();

        $class = ClassRoom::first();
        $this->assertSame('3ème A', $class->name);

        $this->actingAs($this->admin($school))
            ->put("/admin/classes/{$class->id}", ['name' => '3ème A1', 'capacity' => 40])
            ->assertSessionHasNoErrors();

        $this->assertSame('3ème A1', $class->fresh()->name);
    }

    public function test_class_name_must_be_unique_within_the_same_school_only(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        ClassRoom::factory()->create(['school_id' => $schoolA->id, 'name' => '3ème A']);

        $this->actingAs($this->admin($schoolA))
            ->post('/admin/classes', ['name' => '3ème A'])
            ->assertSessionHasErrors('name');

        // Le même nom est autorisé dans une autre école.
        $this->actingAs($this->admin($schoolB))
            ->post('/admin/classes', ['name' => '3ème A'])
            ->assertSessionHasNoErrors();
    }

    public function test_admin_can_create_a_student_and_associate_a_guardian(): void
    {
        $school = School::factory()->create();
        $class = ClassRoom::factory()->create(['school_id' => $school->id]);
        $guardian = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $admin = $this->admin($school);

        $this->actingAs($admin)->post('/admin/students', [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'class_id' => $class->id,
            'guardian_ids' => [$guardian->id],
        ])->assertSessionHasNoErrors();

        $student = Student::where('first_name', 'Jean')->firstOrFail();
        $this->assertTrue($student->guardians()->whereKey($guardian->id)->exists());

        $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            'first_name' => 'Jean',
            'last_name' => 'Dupont-Martin',
            'class_id' => $class->id,
            'guardian_ids' => [],
        ])->assertSessionHasNoErrors();

        $student->refresh();
        $this->assertSame('Dupont-Martin', $student->last_name);
        $this->assertFalse($student->guardians()->exists());
    }

    public function test_admin_cannot_assign_a_class_from_another_school_to_a_student(): void
    {
        $school = School::factory()->create();
        $otherSchool = School::factory()->create();
        $foreignClass = ClassRoom::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($this->admin($school))
            ->post('/admin/students', ['first_name' => 'Jean', 'last_name' => 'Dupont', 'class_id' => $foreignClass->id])
            ->assertSessionHasErrors('class_id');
    }

    public function test_teacher_cannot_manage_reference_data(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->forSchool($school)->create();

        $this->actingAs($teacher)->get('/admin/classes')->assertForbidden();
        $this->actingAs($teacher)->post('/admin/classes', ['name' => 'X'])->assertForbidden();
        $this->actingAs($teacher)->get('/admin/students')->assertForbidden();
    }

    public function test_censeur_can_create_classes_and_subjects_and_view_school_resources(): void
    {
        $school = School::factory()->create();
        $censeur = $this->censeur($school);

        foreach ([
            '/censeur/classes' => 'Admin/Classes/Index',
            '/censeur/subjects' => 'Admin/Subjects/Index',
            '/censeur/teachers' => 'Admin/Teachers/Index',
            '/censeur/guardians' => 'Admin/Guardians/Index',
            '/censeur/students' => 'Admin/Students/Index',
        ] as $path => $component) {
            $this->actingAsVerifiedCenseur($censeur)
                ->get($path)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }

        $this->actingAsVerifiedCenseur($censeur)
            ->post('/censeur/classes', ['name' => '3ème C', 'capacity' => 32])
            ->assertSessionHasNoErrors();

        $class = ClassRoom::where('school_id', $school->id)
            ->where('name', '3ème C')
            ->firstOrFail();

        $this->actingAsVerifiedCenseur($censeur)
            ->post('/censeur/subjects', [
                'name' => 'Sciences',
                'code' => 'SCI',
                'class_ids' => [$class->id],
            ])
            ->assertSessionHasNoErrors();

        $subject = Subject::where('school_id', $school->id)
            ->where('name', 'Sciences')
            ->firstOrFail();

        $this->assertTrue($subject->classes()->whereKey($class->id)->exists());

        $guardian = ParentGuardian::factory()->create(['school_id' => $school->id]);
        $student = Student::factory()->create([
            'school_id' => $school->id,
            'class_id' => $class->id,
        ]);

        $this->actingAsVerifiedCenseur($censeur)
            ->put("/censeur/classes/{$class->id}", ['name' => '3ème D'])
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->delete("/censeur/classes/{$class->id}")
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->put("/censeur/subjects/{$subject->id}", ['name' => 'Biologie'])
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->delete("/censeur/subjects/{$subject->id}")
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->put("/censeur/students/{$student->id}", ['first_name' => 'Modifié'])
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->delete("/censeur/students/{$student->id}")
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->put("/censeur/guardians/{$guardian->id}", ['name' => 'Modifié'])
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->delete("/censeur/guardians/{$guardian->id}")
            ->assertNotFound();

        $this->actingAsVerifiedCenseur($censeur)
            ->post('/censeur/teachers', [])
            ->assertMethodNotAllowed();
    }
}
