<?php

namespace Tests\Feature\ExamBank;

use App\Enums\ExamResourceStatus;
use App\Enums\Role;
use App\Models\ExamResource;
use App\Models\PlatformLevel;
use App\Models\PlatformSubject;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Administration de la banque d'épreuves au niveau plateforme.
 */
class PlatformExamBankManagementTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'role' => Role::PlatformAdmin,
            'school_id' => null,
        ]);
    }

    public function test_the_platform_exam_bank_is_restricted_to_platform_admins(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->get(route('platform.exam-bank.index'))
            ->assertForbidden();
    }

    public function test_a_platform_admin_can_list_the_exam_bank_with_stats(): void
    {
        ExamResource::factory()->published()->create(['title' => 'Épreuve publiée']);
        ExamResource::factory()->create(['title' => 'Brouillon']);

        $this->actingAs($this->platformAdmin())
            ->get(route('platform.exam-bank.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/ExamBank/Index')
                ->has('resources.data', 2)
                ->where('stats.total', 2)
                ->where('stats.published', 1)
                ->where('stats.draft', 1)
            );
    }

    public function test_a_platform_admin_can_create_a_resource(): void
    {
        $level = PlatformLevel::factory()->create();
        $subject = PlatformSubject::factory()->create();

        $response = $this->actingAs($this->platformAdmin())->post(route('platform.exam-bank.store'), [
            'title' => 'Épreuve de mathématiques',
            'description' => 'Sujet complet.',
            'platform_level_id' => $level->id,
            'platform_subject_id' => $subject->id,
            'type' => 'epreuve',
            'difficulty' => 'medium',
            'status' => 'published',
            'duration_minutes' => 90,
            'is_free' => true,
        ]);

        $resource = ExamResource::firstWhere('title', 'Épreuve de mathématiques');

        $this->assertNotNull($resource);
        $this->assertNull($resource->school_id);
        $this->assertTrue($resource->isPublished());
        $this->assertNotNull($resource->published_at);

        $response->assertRedirect(route('platform.exam-bank.show', $resource));
    }

    public function test_a_platform_admin_can_change_a_resources_status(): void
    {
        $resource = ExamResource::factory()->create();

        $this->actingAs($this->platformAdmin())
            ->patch(route('platform.exam-bank.status', $resource), ['status' => 'published'])
            ->assertRedirect();

        $resource->refresh();

        $this->assertSame(ExamResourceStatus::Published, $resource->status);
        $this->assertNotNull($resource->published_at);
    }

    public function test_a_platform_admin_can_manage_reference_levels_and_subjects(): void
    {
        $this->actingAs($this->platformAdmin())
            ->post(route('platform.exam-bank.levels.store'), ['name' => 'Terminale', 'code' => 'Tle'])
            ->assertRedirect();

        $this->actingAs($this->platformAdmin())
            ->post(route('platform.exam-bank.subjects.store'), ['name' => 'Physique', 'code' => 'PC'])
            ->assertRedirect();

        $this->assertDatabaseHas('platform_levels', ['name' => 'Terminale']);
        $this->assertDatabaseHas('platform_subjects', ['name' => 'Physique']);

        $level = PlatformLevel::firstWhere('name', 'Terminale');

        $this->actingAs($this->platformAdmin())
            ->get(route('platform.exam-bank.references'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/ExamBank/References')
                ->has('levels')
                ->has('subjects')
            );

        $this->actingAs($this->platformAdmin())
            ->delete(route('platform.exam-bank.levels.destroy', $level))
            ->assertRedirect();

        $this->assertDatabaseMissing('platform_levels', ['name' => 'Terminale']);
    }

    public function test_a_school_admin_cannot_manage_platform_references(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->post(route('platform.exam-bank.levels.store'), ['name' => 'Terminale'])
            ->assertForbidden();
    }
}
