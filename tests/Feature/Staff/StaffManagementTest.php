<?php

namespace Tests\Feature\Staff;

use App\Enums\Role;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Administration → Personnel.
 */
class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(School $school): User
    {
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->markSensitiveTwoFactorVerified('manage_users');

        return $admin;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Aïcha',
            'last_name' => 'Sossou',
            'email' => 'aicha.sossou@ecole.test',
            'phone' => '+229 01 97 11 22 33',
            'role' => Role::Teacher->value,
            'is_active' => true,
            'password' => 'motdepasse123',
        ], $overrides);
    }

    public function test_the_list_shows_the_school_staff_with_counts(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        User::factory()->teacher()->forSchool($school)->create(['name' => 'Paul Dossou']);
        User::factory()->inactive()->forSchool($school)->create(['name' => 'Ibrahim Sali']);

        $this->get(route('admin.staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Staff/Index')
                // L'admin connecté est lui-même un membre du personnel de son école.
                ->has('staff', 3)
                ->has('roles')
                ->where('counts.total', 3)
                ->where('counts.active', 2)
                ->where('counts.inactive', 1)
                ->where('staff.1.first_name', 'Ibrahim')
                ->where('staff.2.full_name', 'Paul Dossou')
            );
    }

    public function test_the_list_never_leaks_the_staff_of_another_school(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $this->admin($school);

        User::factory()->teacher()->forSchool($other)->create(['name' => 'Collègue Étranger']);

        // Seul l'admin de l'école courante doit être listé.
        $this->get(route('admin.staff.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('staff', 1))
            ->assertDontSee('Collègue Étranger');
    }

    public function test_the_search_filter_is_applied_server_side(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        User::factory()->teacher()->forSchool($school)->create(['name' => 'Paul Dossou']);
        User::factory()->teacher()->forSchool($school)->create(['name' => 'Aïcha Sossou']);

        $this->get(route('admin.staff.index', ['search' => 'Sossou']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('staff', 1)
                ->where('staff.0.full_name', 'Aïcha Sossou')
                ->where('filters.search', 'Sossou')
            );
    }

    public function test_an_admin_can_add_a_teacher_and_a_teacher_record_is_created(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $member = User::where('email', 'aicha.sossou@ecole.test')->firstOrFail();

        $this->assertSame('Aïcha Sossou', $member->name);
        $this->assertSame($school->id, $member->school_id);
        $this->assertSame(Role::Teacher, $member->role);

        // Un enseignant doit avoir une fiche `teachers` : elle porte les affectations.
        $this->assertDatabaseHas('teachers', [
            'school_id' => $school->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_no_teacher_record_is_created_for_a_secretary(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload([
            'role' => Role::Secretary->value,
            'email' => 'secretariat@ecole.test',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('teachers', [
            'user_id' => User::where('email', 'secretariat@ecole.test')->value('id'),
        ]);
    }

    public function test_a_member_can_receive_a_photo_and_removing_it_deletes_the_file(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload([
            'photo' => UploadedFile::fake()->image('portrait.jpg', 300, 300),
        ]))->assertSessionHasNoErrors();

        $member = User::where('email', 'aicha.sossou@ecole.test')->firstOrFail();

        $this->assertNotNull($member->photo_path);
        Storage::disk('public')->assertExists($member->photo_path);

        $this->put(route('admin.staff.update', $member), $this->payload([
            'email' => $member->email,
            'remove_photo' => true,
        ]))->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($member->fresh()->photo_path);
    }

    public function test_the_email_must_stay_unique_but_a_member_can_keep_its_own_email(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $member = User::where('email', 'aicha.sossou@ecole.test')->firstOrFail();

        // Modification sans changer l'email : la règle unique ignore sa propre ligne.
        $this->put(route('admin.staff.update', $member), $this->payload([
            'phone' => '+229 01 97 99 88 77',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(
            '+229 01 97 99 88 77',
            $member->fresh()->phone
        );
    }

    public function test_an_email_already_used_by_another_member_is_rejected(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->post(route('admin.staff.store'), $this->payload([
            'first_name' => 'Autre',
            'last_name' => 'Membre',
            'password' => 'motdepasse123',
        ]))->assertSessionHasErrors('email');
    }

    public function test_an_unchanged_password_is_not_overwritten_on_update(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $member = User::where('email', 'aicha.sossou@ecole.test')->firstOrFail();
        $hash = $member->password;

        $this->put(route('admin.staff.update', $member), $this->payload([
            'email' => $member->email,
            'password' => '',
        ]))->assertSessionHasNoErrors();

        $this->assertSame($hash, $member->fresh()->password);
    }

    public function test_a_parent_role_cannot_be_assigned_to_the_staff(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload([
            'role' => Role::Parent->value,
        ]))->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'aicha.sossou@ecole.test']);
    }

    public function test_an_admin_of_another_school_cannot_alter_this_school_staff(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();

        $victim = User::factory()->teacher()->forSchool($school)->create([
            'name' => 'Paul Dossou',
        ]);

        $this->actingAs(
            User::factory()->admin()->forSchool($other)->create()
        )->markSensitiveTwoFactorVerified('manage_users');

        $this->put(route('admin.staff.update', $victim), $this->payload([
            'email' => 'pirate@ecole.test',
        ]))->assertForbidden();

        $this->post(route('admin.staff.toggle', $victim))->assertForbidden();
        $this->delete(route('admin.staff.destroy', $victim))->assertForbidden();

        $this->assertNotSame('Pirate', $victim->fresh()->name);
        $this->assertNotSame('pirate@ecole.test', $victim->fresh()->email);
    }

    public function test_a_member_cannot_deactivate_or_delete_their_own_account(): void
    {
        $school = School::factory()->create();
        $admin = $this->admin($school);

        $this->put(route('admin.staff.update', $admin), $this->payload([
            'first_name' => 'Direction',
            'last_name' => 'Ecole',
            'email' => $admin->email,
            'role' => Role::Admin->value,
            'is_active' => false,
        ]))->assertStatus(422);

        $this->post(route('admin.staff.toggle', $admin))->assertStatus(422);
        $this->delete(route('admin.staff.destroy', $admin))->assertStatus(422);

        $this->assertTrue($admin->fresh()->is_active);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_toggling_the_access_flips_the_active_flag(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $member = User::where('email', 'aicha.sossou@ecole.test')->firstOrFail();

        $this->post(route('admin.staff.toggle', $member))
            ->assertSessionHasNoErrors();

        $this->assertFalse($member->fresh()->is_active);

        $this->post(route('admin.staff.toggle', $member))
            ->assertSessionHasNoErrors();

        $this->assertTrue($member->fresh()->is_active);
    }

    public function test_a_teacher_with_academic_history_is_deactivated_instead_of_deleted(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $member = User::factory()->teacher()->forSchool($school)->create();
        $teacher = Teacher::create([
            'school_id' => $school->id,
            'user_id' => $member->id,
        ]);

        $class = ClassRoom::factory()->create(['school_id' => $school->id]);
        $subject = Subject::factory()->create(['school_id' => $school->id]);

        $teacher->assignments()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        $this->delete(route('admin.staff.destroy', $member))
            ->assertSessionHas('error');

        // L'historique des notes reste lisible : on désactive au lieu d'effacer.
        $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => false]);
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id]);
    }

    public function test_a_teacher_without_history_is_deleted_with_its_teacher_record(): void
    {
        $school = School::factory()->create();
        $this->admin($school);

        $this->post(route('admin.staff.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $member = User::where('email', 'aicha.sossou@ecole.test')->firstOrFail();

        $this->delete(route('admin.staff.destroy', $member))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
        $this->assertDatabaseMissing('teachers', ['user_id' => $member->id]);
    }

    public function test_a_teacher_cannot_reach_the_staff_page(): void
    {
        $school = School::factory()->create();

        $this->actingAs(User::factory()->teacher()->forSchool($school)->create())
            ->get(route('admin.staff.index'))
            ->assertForbidden();
    }

    public function test_writing_staff_requires_a_fresh_two_factor_verification(): void
    {
        $school = School::factory()->create();

        $admin = User::factory()->admin()->forSchool($school)->create();

        // Sans la revalidation 2FA sensible, l'action est mise en attente de challenge.
        $this->actingAs($admin)
            ->post(route('admin.staff.store'), $this->payload())
            ->assertRedirectContains('/two-factor/challenge');

        $this->assertDatabaseMissing('users', ['email' => 'aicha.sossou@ecole.test']);
    }
}
