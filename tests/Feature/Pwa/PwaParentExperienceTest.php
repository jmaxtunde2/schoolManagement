<?php

namespace Tests\Feature\Pwa;

use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Services\ParentAccess\ParentAccessSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Expérience PWA et accès parent intelligent (une seule PWA CoriSchool,
 * établissement retrouvé depuis la session parent).
 */
class PwaParentExperienceTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private ParentGuardian $parent;

    private Student $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
        $this->parent = ParentGuardian::factory()->create([
            'school_id' => $this->school->id,
            'name' => 'Awa KOFFI',
        ]);
        $this->child = Student::factory()->create(['school_id' => $this->school->id]);
        $this->parent->students()->attach($this->child->id, ['relationship' => 'mère']);
    }

    private function createParentSession(School $school, ParentGuardian $parent): array
    {
        return app(ParentAccessSessionService::class)
            ->createSession($parent, $school, null, request());
    }

    public function test_mini_site_keeps_the_activation_flow_without_a_parent_session(): void
    {
        $this->get("/{$this->school->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('parentAccess', null));
    }

    public function test_mini_site_links_directly_to_the_parent_space_with_a_valid_session(): void
    {
        $session = $this->createParentSession($this->school, $this->parent);

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get("/{$this->school->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('parentAccess.parent_name', 'Awa KOFFI')
                ->where('parentAccess.dashboard_url', route('parent-access.dashboard.slug', [
                    'slug' => $this->school->slug,
                ])));
    }

    public function test_mini_site_does_not_expose_a_session_from_another_school(): void
    {
        $otherSchool = School::factory()->create();
        $otherParent = ParentGuardian::factory()->create(['school_id' => $otherSchool->id]);
        $otherChild = Student::factory()->create(['school_id' => $otherSchool->id]);
        $otherParent->students()->attach($otherChild->id, ['relationship' => 'père']);

        $session = $this->createParentSession($otherSchool, $otherParent);

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get("/{$this->school->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('parentAccess', null));
    }

    public function test_pwa_start_redirects_a_valid_parent_to_their_own_school_dashboard(): void
    {
        $session = $this->createParentSession($this->school, $this->parent);

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get('/app')
            ->assertRedirect(route('parent-access.dashboard.slug', [
                'slug' => $this->school->slug,
            ]));
    }

    public function test_pwa_start_renders_a_safe_recovery_page_without_a_session(): void
    {
        $this->get('/app')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PwaWelcome')
                ->where('schoolsUrl', route('public.schools.index'))
                ->where('homeUrl', route('public.platform.landing')));
    }

    public function test_pwa_start_ignores_an_expired_parent_session(): void
    {
        $session = $this->createParentSession($this->school, $this->parent);
        $session['session']->update(['expires_at' => now()->subMinute()]);

        $this->withCookie('parent_access_token', $session['plain_token'])
            ->get('/app')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/PwaWelcome'));
    }

    public function test_manifest_uses_the_pwa_bootstrap_as_start_url(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('/app', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('CoriSchool', $manifest['name']);
    }

    public function test_manifest_uses_the_official_root_icons(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $sources = array_column($manifest['icons'], 'src');

        $this->assertContains('/web-app-manifest-192x192.png', $sources);
        $this->assertContains('/web-app-manifest-512x512.png', $sources);
        $this->assertStringNotContainsString('/icons/', implode(',', $sources));

        foreach ($sources as $source) {
            $this->assertFileExists(public_path(ltrim($source, '/')));
        }
    }
}
