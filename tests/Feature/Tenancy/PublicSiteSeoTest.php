<?php

namespace Tests\Feature\Tenancy;

use App\Models\School;
use App\Models\SchoolDomain;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function verifiedAdmin(School $school): User
    {
        $admin = User::factory()->admin()->forSchool($school)->create([
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin)->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ]);

        return $admin;
    }

    public function test_root_slug_route_renders_landing_with_seo_data(): void
    {
        $school = School::factory()->create(['name' => 'École Horizon']);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => 'École Horizon',
            'slogan' => 'Excellence pour tous',
            'city' => 'Parakou',
            'country' => 'Bénin',
            'website' => 'https://horizon.example',
        ]);

        $this->get('/'.$school->slug)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('school.name', 'École Horizon')
                ->where('school.meta_title', 'École Horizon – Excellence pour tous')
                ->where('school.canonical_url', url('/'.$school->slug))
                ->where('structuredData.@type', 'EducationalOrganization')
                ->where('structuredData.name', 'École Horizon')
                ->where('structuredData.address.addressLocality', 'Parakou')
                ->where('structuredData.address.addressCountry', 'Bénin')
                ->where('structuredData.sameAs.0', 'https://horizon.example'));
    }

    public function test_root_slug_route_returns_404_for_unknown_slug(): void
    {
        $this->get('/etablissement-introuvable')->assertNotFound();
    }

    public function test_reserved_segments_are_never_treated_as_school_pages(): void
    {
        School::factory()->create(['name' => 'École Horizon']);

        $this->get('/profile')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
        $this->get('/up')->assertOk();
    }

    public function test_sitemap_lists_active_schools_only(): void
    {
        $active = School::factory()->create(['name' => 'École Active', 'is_active' => true]);
        $inactive = School::factory()->create(['name' => 'École Fermée', 'is_active' => false]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee(url('/'), false);
        $response->assertSee(url('/'.$active->slug), false);
        $response->assertDontSee($inactive->slug);
    }

    public function test_robots_points_to_the_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $response->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    public function test_school_admin_can_set_a_clean_public_slug(): void
    {
        $school = School::factory()->create(['name' => 'Ketry School']);
        $this->verifiedAdmin($school);

        $this->put('/admin/settings/public-site', [
            'slug' => 'ketry-school',
            'mission' => 'Former les citoyens de demain.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ketry-school', $school->fresh()->slug);
        $this->assertSame(url('/ketry-school'), route('public.school.slug', 'ketry-school'));
    }

    public function test_public_slug_rejects_reserved_duplicate_and_invalid_values(): void
    {
        $school = School::factory()->create(['name' => 'Ketry School']);
        School::factory()->create(['name' => 'Autre', 'slug' => 'deja-pris']);
        $this->verifiedAdmin($school);

        $this->put('/admin/settings/public-site', ['slug' => 'login'])
            ->assertSessionHasErrors('slug');

        $this->put('/admin/settings/public-site', ['slug' => 'deja-pris'])
            ->assertSessionHasErrors('slug');

        $this->put('/admin/settings/public-site', ['slug' => 'Pas Valide'])
            ->assertSessionHasErrors('slug');

        $this->assertNotSame('login', $school->fresh()->slug);
        $this->assertSame('deja-pris', School::query()->where('name', 'Autre')->value('slug'));
    }

    public function test_canonical_always_uses_the_corischool_slug_even_with_a_custom_domain(): void
    {
        $school = School::factory()->create(['name' => 'École Horizon']);
        SchoolDomain::create([
            'school_id' => $school->id,
            'domain' => 'horizon.public.test',
            'is_primary' => true,
            'verified' => true,
        ]);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => 'École Horizon',
            'website' => 'https://horizon.example',
        ]);

        $this->get('http://horizon.public.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('school.canonical_url', $school->publicUrl())
                ->where('school.public_url', $school->publicUrl())
                ->where('school.custom_domain_url', 'https://horizon.public.test')
                ->where('school.website', 'https://horizon.example')
                ->where('school.platform_url', rtrim(config('app.url'), '/')));
    }

    public function test_settings_always_publish_the_corischool_slug_url(): void
    {
        $school = School::factory()->create(['name' => 'Ketry School']);
        SchoolDomain::create([
            'school_id' => $school->id,
            'domain' => 'ketry.public.test',
            'is_primary' => true,
            'verified' => true,
        ]);
        $this->verifiedAdmin($school);

        $this->get('/admin/settings/public-site')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/PublicSite')
                ->where('school.public_url', $school->publicUrl())
                ->where('school.custom_domain_url', 'https://ketry.public.test')
                ->where('school.public_url_root', rtrim(config('app.url'), '/')));
    }
}
