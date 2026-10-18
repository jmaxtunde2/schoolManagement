<?php

namespace Tests\Feature\Documents;

use App\Enums\Role;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Centre de documents (phase 6).
 */
class DocumentCenterTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $director;

    private AcademicYear $year;

    private ClassRoom $primaryClass;

    private ClassRoom $secondaryClass;

    private Student $primaryStudent;

    private Student $secondaryStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        $this->admin = User::factory()->admin()->forSchool($this->school)->create();
        $this->director = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Director,
        ]);

        $this->year = AcademicYear::factory()->create([
            'school_id' => $this->school->id,
            'is_current' => true,
        ]);

        AcademicPeriod::create([
            'school_id' => $this->school->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Trimestre 1',
            'position' => 1,
            'starts_at' => now()->startOfYear()->toDateString(),
            'ends_at' => now()->endOfYear()->toDateString(),
            'is_closed' => false,
        ]);

        $this->primaryClass = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'CM2 A',
            'level' => 'CM2',
        ]);
        $this->secondaryClass = ClassRoom::factory()->create([
            'school_id' => $this->school->id,
            'name' => '6ème A',
            'level' => '6ème',
        ]);

        $this->primaryStudent = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->primaryClass->id,
        ]);
        $this->secondaryStudent = Student::factory()->create([
            'school_id' => $this->school->id,
            'class_id' => $this->secondaryClass->id,
        ]);
    }

    public function test_the_admin_sees_the_document_center(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Documents/Index')
                ->has('classes', 2)
                ->has('students', 2)
                ->where('canViewFinance', true)
                ->etc()
            );
    }

    public function test_the_admin_can_generate_a_certificate_of_enrolment(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.documents.certificate', $this->primaryStudent->id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('%PDF', (string) $response->getContent());
    }

    public function test_the_admin_can_generate_an_attestation_of_enrolment(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.documents.attestation', $this->primaryStudent->id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_a_class_list_can_be_generated(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.documents.class-list', $this->primaryClass->id));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_general_lists_and_reports_can_be_generated(): void
    {
        foreach (['students', 'classes', 'statistics', 'finance'] as $document) {
            $this->actingAs($this->admin)
                ->get(route("admin.documents.{$document}"))
                ->assertOk();
        }
    }

    public function test_the_director_only_reaches_primary_documents(): void
    {
        $this->actingAs($this->director)
            ->get(route('director.documents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Documents/Index')
                ->has('classes', 1)
                ->has('students', 1)
                ->where('students.0.id', $this->primaryStudent->id)
                ->where('canViewFinance', false)
                ->etc()
            );

        $this->actingAs($this->director)
            ->get(route('director.documents.certificate', $this->secondaryStudent->id))
            ->assertForbidden();
    }

    public function test_documents_are_isolated_per_school(): void
    {
        $otherSchool = School::factory()->create();
        $otherClass = ClassRoom::factory()->create(['school_id' => $otherSchool->id]);
        $otherStudent = Student::factory()->create([
            'school_id' => $otherSchool->id,
            'class_id' => $otherClass->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.documents.certificate', $otherStudent->id))
            ->assertNotFound();
    }
}
