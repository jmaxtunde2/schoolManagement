<?php

namespace Tests\Feature\Preinscriptions;

use App\Enums\PreinscriptionStatus;
use App\Enums\Role;
use App\Models\ClassRoom;
use App\Models\Preinscription;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Préinscription en ligne (phase 7).
 */
class PreinscriptionTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    private User $director;

    private ClassRoom $primaryClass;

    private ClassRoom $secondaryClass;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create(['name' => 'Groupe Scolaire Ketry']);

        $this->admin = User::factory()->admin()->forSchool($this->school)->create();
        $this->director = User::factory()->create([
            'school_id' => $this->school->id,
            'role' => Role::Director,
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
    }

    public function test_the_public_preinscription_form_is_available(): void
    {
        $this->get(route('public.school.preinscription', $this->school->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Preinscription')
                ->where('school.name', 'Groupe Scolaire Ketry')
                ->has('levels', 2)
                ->etc()
            );
    }

    public function test_an_inactive_school_has_no_public_form(): void
    {
        $this->school->update(['is_active' => false]);

        $this->get(route('public.school.preinscription', $this->school->slug))
            ->assertNotFound();
    }

    public function test_a_public_visitor_can_submit_a_demand(): void
    {
        Storage::fake('public');

        $this->post(route('public.school.preinscription.store', $this->school->slug), [
            'first_name' => 'Awa',
            'last_name' => 'Dossou',
            'birth_date' => now()->subYears(8)->toDateString(),
            'gender' => 'F',
            'requested_level' => 'CM2',
            'parent_name' => 'Koffi Dossou',
            'parent_phone' => '+229 01 97000000',
            'parent_email' => 'koffi@example.com',
            'parent_relationship' => 'Père',
            'notes' => 'Merci de contacter en soirée.',
            'documents' => [UploadedFile::fake()->create('acte-naissance.pdf', 120, 'application/pdf')],
        ])->assertRedirect(route('public.school.preinscription', $this->school->slug));

        $preinscription = Preinscription::withoutGlobalScopes()->firstOrFail();

        $this->assertSame($this->school->id, $preinscription->school_id);
        $this->assertSame('Awa', $preinscription->first_name);
        $this->assertSame(PreinscriptionStatus::Pending, $preinscription->status);
        $this->assertCount(1, $preinscription->documents);

        Storage::disk('public')->assertExists($preinscription->documents[0]);
    }

    public function test_the_public_form_requires_the_essential_fields(): void
    {
        $this->post(route('public.school.preinscription.store', $this->school->slug), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'parent_name', 'parent_phone']);
    }

    public function test_the_admin_sees_pending_demands(): void
    {
        Preinscription::factory()->create(['school_id' => $this->school->id, 'first_name' => 'Awa']);

        $this->actingAs($this->admin)
            ->get(route('admin.preinscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Preinscriptions/Index')
                ->has('preinscriptions', 1)
                ->where('preinscriptions.0.first_name', 'Awa')
                ->has('classes', 2)
                ->etc()
            );
    }

    public function test_validating_a_demand_creates_a_student_and_a_guardian(): void
    {
        $preinscription = Preinscription::factory()->create([
            'school_id' => $this->school->id,
            'first_name' => 'Awa',
            'last_name' => 'Dossou',
            'parent_name' => 'Koffi Dossou',
            'parent_phone' => '+229 01 97000000',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.preinscriptions.approve', $preinscription->id), [
                'class_id' => $this->primaryClass->id,
            ])
            ->assertRedirect();

        $preinscription->refresh();

        $this->assertSame(PreinscriptionStatus::Approved, $preinscription->status);
        $this->assertNotNull($preinscription->student_id);
        $this->assertSame($this->admin->id, $preinscription->reviewed_by);

        $student = Student::withoutGlobalScopes()->findOrFail($preinscription->student_id);
        $this->assertSame('Awa', $student->first_name);
        $this->assertSame($this->primaryClass->id, $student->class_id);
        $this->assertSame(1, $student->guardians()->count());

        $this->assertDatabaseHas('parents', [
            'school_id' => $this->school->id,
            'name' => 'Koffi Dossou',
            'phone' => '+229 01 97000000',
        ]);
    }

    public function test_a_demand_can_be_rejected_with_a_reason(): void
    {
        $preinscription = Preinscription::factory()->create(['school_id' => $this->school->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.preinscriptions.reject', $preinscription->id), [
                'reason' => 'Dossier incomplet.',
            ])
            ->assertRedirect();

        $preinscription->refresh();

        $this->assertSame(PreinscriptionStatus::Rejected, $preinscription->status);
        $this->assertSame('Dossier incomplet.', $preinscription->rejection_reason);
        $this->assertNull($preinscription->student_id);
    }

    public function test_the_director_only_sees_primary_demands(): void
    {
        Preinscription::factory()->create(['school_id' => $this->school->id, 'requested_level' => 'CM2']);
        Preinscription::factory()->create(['school_id' => $this->school->id, 'requested_level' => '6ème']);

        $this->actingAs($this->director)
            ->get(route('director.preinscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Preinscriptions/Index')
                ->has('preinscriptions', 1)
                ->where('preinscriptions.0.requested_level', 'CM2')
                ->etc()
            );
    }

    public function test_the_director_cannot_validate_a_secondary_demand(): void
    {
        $preinscription = Preinscription::factory()->create([
            'school_id' => $this->school->id,
            'requested_level' => '6ème',
        ]);

        $this->actingAs($this->director)
            ->post(route('director.preinscriptions.approve', $preinscription->id), [])
            ->assertForbidden();
    }

    public function test_demands_are_isolated_per_school(): void
    {
        $otherSchool = School::factory()->create();
        $other = Preinscription::factory()->create(['school_id' => $otherSchool->id]);

        $this->actingAs($this->admin)
            ->post(route('admin.preinscriptions.approve', $other->id), [])
            ->assertNotFound();
    }
}
