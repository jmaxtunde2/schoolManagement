<?php

namespace Tests\Feature\Reference;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Photos d'élève et second numéro de téléphone du responsable.
 *
 * Ces deux champs sont gérés par les formulaires admin existants : pas de
 * route dédiée, la photo transite par le même POST/PUT que la fiche.
 */
class StudentPhotoAndGuardianPhoneTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private AcademicYear $year;

    private ClassRoom $classRoom;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->school = School::factory()->create();
        $this->year = AcademicYear::factory()->forSchool($this->school)->create();
        $this->classRoom = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => '5ème B',
        ]);

        $this->actingAs(
            User::factory()->admin()->forSchool($this->school)->create()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Yasmine',
            'last_name' => 'Bonou',
            'matricule' => 'EL-0001',
            'class_id' => $this->classRoom->id,
            'guardian_ids' => [],
        ], $overrides);
    }

    public function test_a_student_can_be_created_with_a_photo(): void
    {
        $this->post(route('admin.students.store'), $this->studentPayload([
            'photo' => UploadedFile::fake()->image('yasmine.png', 200, 200),
        ]))->assertSessionHasNoErrors();

        $student = Student::where('school_id', $this->school->id)->firstOrFail();

        $this->assertNotNull($student->photo_path);
        Storage::disk('public')->assertExists($student->photo_path);
    }

    public function test_the_photo_is_exposed_to_the_list(): void
    {
        $this->post(route('admin.students.store'), $this->studentPayload([
            'photo' => UploadedFile::fake()->image('yasmine.png', 200, 200),
        ]))->assertSessionHasNoErrors();

        $student = Student::where('school_id', $this->school->id)->firstOrFail();

        $this->get(route('admin.students.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Students/Index')
                // La liste est paginée : les élèves sont sous `students.data`.
                ->has('students.data', 1)
                ->where('students.data.0.photo_url', $student->photo_url)
            );
    }

    public function test_replacing_a_photo_deletes_the_previous_file(): void
    {
        $this->post(route('admin.students.store'), $this->studentPayload([
            'matricule' => 'EL-0002',
            'photo' => UploadedFile::fake()->image('premiere.png', 200, 200),
        ]))->assertSessionHasNoErrors();

        $student = Student::where('school_id', $this->school->id)->firstOrFail();
        $previous = $student->photo_path;
        Storage::disk('public')->assertExists($previous);

        $this->put(route('admin.students.update', $student), $this->studentPayload([
            'matricule' => 'EL-0002',
            'photo' => UploadedFile::fake()->image('seconde.png', 200, 200),
        ]))->assertSessionHasNoErrors();

        $student->refresh();

        $this->assertNotSame($previous, $student->photo_path);
        Storage::disk('public')->assertMissing($previous);
        Storage::disk('public')->assertExists($student->photo_path);
    }

    public function test_removing_a_photo_clears_the_field_and_the_file(): void
    {
        $this->post(route('admin.students.store'), $this->studentPayload([
            'matricule' => 'EL-0003',
            'photo' => UploadedFile::fake()->image('a-supprimer.png', 200, 200),
        ]))->assertSessionHasNoErrors();

        $student = Student::where('school_id', $this->school->id)->firstOrFail();
        $path = $student->photo_path;
        Storage::disk('public')->assertExists($path);

        $this->put(route('admin.students.update', $student), $this->studentPayload([
            'matricule' => 'EL-0003',
            'remove_photo' => true,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($student->fresh()->photo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_photo_must_be_a_supported_image(): void
    {
        $this->post(route('admin.students.store'), $this->studentPayload([
            'photo' => UploadedFile::fake()->create('contrat.pdf', 10, 'application/pdf'),
        ]))->assertSessionHasErrors('photo');

        $this->assertSame(0, Student::where('school_id', $this->school->id)->count());
    }

    public function test_a_guardian_keeps_a_secondary_phone_number(): void
    {
        $this->post(route('admin.guardians.store'), [
            'name' => 'Rosine Hounkpatin',
            'phone' => '+229 97 11 22 33',
            'phone_secondary' => '+229 66 44 55 66',
            'email' => 'rosine.hounkpatin@ecole.test',
            'password' => 'motdepasse123',
        ])->assertSessionHasNoErrors();

        $guardian = ParentGuardian::where('school_id', $this->school->id)->firstOrFail();

        $this->assertSame('+229 66 44 55 66', $guardian->phone_secondary);
    }

    public function test_the_secondary_phone_is_exposed_to_the_list(): void
    {
        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Sylvie Akplogan',
            'phone' => '+229 95 00 11 22',
            'phone_secondary' => '+229 95 00 33 44',
        ]);

        $this->get(route('admin.guardians.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Guardians/Index')
                ->where('guardians.0.name', $guardian->name)
                ->where('guardians.0.phone_secondary', '+229 95 00 33 44')
            );
    }

    public function test_the_secondary_phone_can_be_updated_and_cleared(): void
    {
        $guardian = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'phone_secondary' => '+229 95 00 33 44',
        ]);

        $this->put(route('admin.guardians.update', $guardian), [
            'name' => $guardian->name,
            'phone' => $guardian->phone,
            'phone_secondary' => '+229 40 10 20 30',
            'email' => $guardian->user?->email ?? 'sylvie.akplogan@ecole.test',
        ])->assertSessionHasNoErrors();

        $this->assertSame('+229 40 10 20 30', $guardian->fresh()->phone_secondary);

        $this->put(route('admin.guardians.update', $guardian), [
            'name' => $guardian->name,
            'phone' => $guardian->phone,
            'phone_secondary' => '',
            'email' => $guardian->user?->email ?? 'sylvie.akplogan@ecole.test',
        ])->assertSessionHasNoErrors();

        $this->assertNull($guardian->fresh()->phone_secondary);
    }

    public function test_an_invalid_secondary_phone_is_rejected(): void
    {
        $this->post(route('admin.guardians.store'), [
            'name' => 'Hervé Zinsou',
            'phone' => '+229 97 55 55 55',
            'phone_secondary' => 'appeler-moi',
            'email' => 'herve.zinsou@ecole.test',
            'password' => 'motdepasse123',
        ])->assertSessionHasErrors('phone_secondary');

        $this->assertSame(0, ParentGuardian::where('school_id', $this->school->id)->count());
    }
}
