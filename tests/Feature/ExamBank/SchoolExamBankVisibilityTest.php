<?php

namespace Tests\Feature\ExamBank;

use App\Enums\Role;
use App\Models\ExamResource;
use App\Models\ExamResourceAttachment;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Consultation de la banque d'épreuves depuis un établissement :
 * cloisonnement par école, visibilité des ressources plateforme publiées
 * et protection des corrigés.
 */
class SchoolExamBankVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_school_only_sees_published_platform_resources_and_its_own(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($schoolA)->create();

        ExamResource::factory()->published()->create(['title' => 'Plateforme publiée']);
        ExamResource::factory()->create(['title' => 'Plateforme brouillon']);
        ExamResource::factory()->forSchool($schoolA)->create(['title' => 'École A']);
        ExamResource::factory()->forSchool($schoolB)->create(['title' => 'École B']);

        $this->actingAs($admin)
            ->get(route('admin.exam-bank.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ExamBank/Index')
                ->has('resources.data', 2)
                ->where('resources.data.0.title', 'École A')
                ->where('resources.data.1.title', 'Plateforme publiée')
                ->where('routePrefix', 'admin')
            );
    }

    public function test_a_school_cannot_view_a_platform_draft_or_another_schools_resource(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($schoolA)->create();

        $platformDraft = ExamResource::factory()->create();
        $otherSchool = ExamResource::factory()->forSchool($schoolB)->create();

        $this->actingAs($admin)
            ->get(route('admin.exam-bank.show', $platformDraft))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.exam-bank.show', $otherSchool))
            ->assertForbidden();
    }

    public function test_a_school_can_open_a_published_platform_resource(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();
        $platformPublished = ExamResource::factory()->published()->create();

        $this->actingAs($admin)
            ->get(route('admin.exam-bank.show', $platformPublished))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ExamBank/Show')
                ->where('resource.id', $platformPublished->id)
            );
    }

    public function test_a_staff_member_can_download_a_document_and_the_counter_is_incremented(): void
    {
        Storage::fake('local');

        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();
        $resource = ExamResource::factory()->published()->create();

        $attachment = ExamResourceAttachment::factory()->create([
            'exam_resource_id' => $resource->id,
        ]);

        Storage::disk('local')->put($attachment->path, 'contenu');

        $this->actingAs($admin)
            ->get(route('admin.exam-bank.download', [$resource, $attachment]))
            ->assertOk();

        $this->assertSame(1, $resource->fresh()->downloads_count);
    }

    public function test_a_teacher_can_download_a_protected_answer_key(): void
    {
        Storage::fake('local');

        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->forSchool($school)->create();
        $resource = ExamResource::factory()->published()->create();

        $answerKey = ExamResourceAttachment::factory()->answerKey()->create([
            'exam_resource_id' => $resource->id,
        ]);

        Storage::disk('local')->put($answerKey->path, 'corrige');

        $this->actingAs($teacher)
            ->get(route('teacher.exam-bank.answer-key', [$resource, $answerKey]))
            ->assertOk();
    }

    public function test_a_regular_attachment_cannot_be_served_through_the_answer_key_route(): void
    {
        Storage::fake('local');

        $school = School::factory()->create();
        $teacher = User::factory()->teacher()->forSchool($school)->create();
        $resource = ExamResource::factory()->published()->create();

        $document = ExamResourceAttachment::factory()->create([
            'exam_resource_id' => $resource->id,
        ]);

        Storage::disk('local')->put($document->path, 'contenu');

        $this->actingAs($teacher)
            ->get(route('teacher.exam-bank.answer-key', [$resource, $document]))
            ->assertNotFound();
    }

    public function test_a_parent_cannot_access_the_school_exam_bank(): void
    {
        $school = School::factory()->create();
        $parent = User::factory()->forSchool($school)->create(['role' => Role::Parent]);

        $this->actingAs($parent)
            ->get(route('admin.exam-bank.index'))
            ->assertForbidden();
    }
}
