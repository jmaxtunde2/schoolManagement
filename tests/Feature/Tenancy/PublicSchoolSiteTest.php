<?php

namespace Tests\Feature\Tenancy;

use App\Models\ClassRoom;
use App\Models\School;
use App\Models\SchoolDomain;
use App\Models\SchoolPublicGalleryItem;
use App\Models\SchoolPublicTestimonial;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSchoolSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Cache::flush();
    }

    private function addDomain(School $school, string $domain, bool $verified = true): void
    {
        SchoolDomain::create([
            'school_id' => $school->id,
            'domain' => $domain,
            'is_primary' => true,
            'verified' => $verified,
        ]);
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

    public function test_public_site_resolves_each_verified_school_and_limits_public_data(): void
    {
        $schoolA = School::factory()->create(['name' => 'École Horizon']);
        $schoolB = School::factory()->create(['name' => 'Institut Baobab']);
        $this->addDomain($schoolA, 'horizon.public.test');
        $this->addDomain($schoolB, 'baobab.public.test');

        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $schoolA->id,
            'school_name' => 'École Horizon',
            'slogan' => 'Grandir par le savoir',
            'description' => 'Présentation de Horizon.',
            'primary_color' => '#116B50',
            'secondary_color' => '#2457A6',
            'options' => [
                'public_site' => [
                    'mission' => 'Former avec exigence.',
                    'vision' => 'Réussir ensemble.',
                    'values' => ['Respect', 'Effort'],
                    'features' => [['title' => 'Un suivi attentif', 'description' => 'Un accompagnement régulier.']],
                ],
            ],
        ]);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $schoolB->id,
            'school_name' => 'Institut Baobab',
            'slogan' => 'Slogan Baobab',
            'primary_color' => '#8A3E18',
        ]);

        $classA = ClassRoom::factory()->create(['school_id' => $schoolA->id]);
        ClassRoom::factory()->create(['school_id' => $schoolB->id]);
        Student::factory()->create([
            'school_id' => $schoolA->id,
            'class_id' => $classA->id,
            'is_active' => true,
        ]);
        Student::factory()->create([
            'school_id' => $schoolB->id,
            'is_active' => true,
        ]);
        $teacherUser = User::factory()->teacher()->forSchool($schoolA)->create();
        Teacher::factory()->create([
            'school_id' => $schoolA->id,
            'user_id' => $teacherUser->id,
        ]);

        SchoolPublicTestimonial::create([
            'school_id' => $schoolA->id,
            'name' => 'Parent Horizon',
            'relationship' => 'Parent',
            'message' => 'Un suivi de qualité.',
        ]);
        SchoolPublicTestimonial::create([
            'school_id' => $schoolB->id,
            'name' => 'Parent Baobab',
            'message' => 'Témoignage privé à cette école.',
        ]);
        $galleryPath = "schools/{$schoolA->id}/public-site/gallery/cour.jpg";
        Storage::disk('public')->put($galleryPath, 'fake-image');
        SchoolPublicGalleryItem::create([
            'school_id' => $schoolA->id,
            'image_path' => $galleryPath,
            'title' => 'Cour de l’école',
            'alt_text' => 'La cour de l’école Horizon',
        ]);

        $this->get('http://horizon.public.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('school.name', 'École Horizon')
                ->where('school.slogan', 'Grandir par le savoir')
                ->where('school.primary_color', '#116B50')
                ->where('school.mission', 'Former avec exigence.')
                ->where('stats.students', 1)
                ->where('stats.teachers', 1)
                ->where('stats.classes', 1)
                ->has('testimonials', 1)
                ->where('testimonials.0.name', 'Parent Horizon')
                ->has('gallery', 1)
                ->where('gallery.0.title', 'Cour de l’école')
                ->missing('school.id')
                ->missing('school.school_id')
                ->missing('school.options'));

        $this->get('http://baobab.public.test/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('school.name', 'Institut Baobab')
                ->where('school.slogan', 'Slogan Baobab')
                ->where('school.primary_color', '#8A3E18')
                ->where('stats.students', 1)
                ->where('stats.teachers', 0)
                ->where('stats.classes', 1)
                ->has('testimonials', 1)
                ->where('testimonials.0.name', 'Parent Baobab')
                ->has('gallery', 0));
    }

    public function test_public_site_rejects_an_unverified_domain(): void
    {
        $school = School::factory()->create(['name' => 'École non vérifiée']);
        $this->addDomain($school, 'waiting.public.test', false);

        $this->get('http://waiting.public.test/')
            ->assertRedirect(route('login'))
            ->assertDontSee('École non vérifiée');
    }

    public function test_public_school_slug_route_works_without_a_verified_domain(): void
    {
        $school = School::factory()->create(['name' => 'École sans domaine']);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => $school->name,
            'slogan' => 'Un accès public par slug.',
        ]);

        $this->get(route('public.school.slug', $school->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('school.name', 'École sans domaine')
                ->where('school.slogan', 'Un accès public par slug.')
                ->where('school.canonical_url', route('public.school.slug', $school->slug)));

        $this->get('/ecole/slug-inexistant')->assertNotFound();
    }

    public function test_school_admin_can_manage_only_its_public_site_content(): void
    {
        $schoolA = School::factory()->create(['name' => 'École A']);
        $schoolB = School::factory()->create(['name' => 'École B']);
        $admin = $this->verifiedAdmin($schoolA);

        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $schoolB->id,
            'school_name' => 'École B',
            'options' => ['public_site' => ['mission' => 'Mission B']],
        ]);
        $foreignTestimonial = SchoolPublicTestimonial::create([
            'school_id' => $schoolB->id,
            'name' => 'Témoin B',
            'message' => 'Contenu de B.',
        ]);

        $this->get('/admin/settings/public-site')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/PublicSite')
                ->where('school.name', 'École A')
                ->where('school.public_url', route('public.school.slug', $schoolA->slug))
                ->has('testimonials', 0));

        $this->put('/admin/settings/public-site', [
            'mission' => 'Former les citoyens de demain.',
            'vision' => 'Une réussite partagée.',
            'values' => ['Respect', 'Persévérance'],
            'features' => [['title' => 'Suivi individualisé', 'description' => 'Un suivi adapté.']],
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1000, 600),
        ])->assertSessionHasNoErrors();

        $settings = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $schoolA->id)
            ->firstOrFail();
        $this->assertSame('Former les citoyens de demain.', data_get($settings->options, 'public_site.mission'));
        $this->assertStringStartsWith("schools/{$schoolA->id}/public-site/hero/", data_get($settings->options, 'public_site.hero_image_path'));
        Storage::disk('public')->assertExists(data_get($settings->options, 'public_site.hero_image_path'));

        $this->post('/admin/settings/public-site/testimonials', [
            'name' => 'Parent A',
            'relationship' => 'Parent d’élève',
            'message' => 'Un témoignage propre à A.',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->post('/admin/settings/public-site/gallery', [
            'image' => UploadedFile::fake()->image('activity.jpg', 800, 600),
            'title' => 'Activité A',
            'alt_text' => 'Élèves pendant une activité',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $ownTestimonial = SchoolPublicTestimonial::withoutGlobalScopes()
            ->where('school_id', $schoolA->id)
            ->firstOrFail();
        $this->put("/admin/settings/public-site/testimonials/{$ownTestimonial->id}", [
            'name' => 'Parent A',
            'relationship' => 'Parent',
            'message' => 'Témoignage mis à jour.',
            'sort_order' => 1,
            'is_active' => false,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_public_testimonials', [
            'id' => $ownTestimonial->id,
            'school_id' => $schoolA->id,
            'message' => 'Témoignage mis à jour.',
            'is_active' => false,
        ]);

        $ownGalleryItem = SchoolPublicGalleryItem::withoutGlobalScopes()
            ->where('school_id', $schoolA->id)
            ->firstOrFail();
        $galleryPath = $ownGalleryItem->image_path;
        $this->put("/admin/settings/public-site/gallery/{$ownGalleryItem->id}", [
            'title' => 'Activité modifiée',
            'alt_text' => 'Élèves en activité pédagogique',
            'is_active' => false,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('school_public_gallery_items', [
            'id' => $ownGalleryItem->id,
            'school_id' => $schoolA->id,
            'title' => 'Activité modifiée',
            'is_active' => false,
        ]);

        $this->put("/admin/settings/public-site/testimonials/{$foreignTestimonial->id}", [
            'name' => 'Intrusion',
            'message' => 'Modification interdite.',
        ])->assertNotFound();

        $this->delete("/admin/settings/public-site/testimonials/{$ownTestimonial->id}")
            ->assertSessionHasNoErrors();
        $this->delete("/admin/settings/public-site/gallery/{$ownGalleryItem->id}")
            ->assertSessionHasNoErrors();

        $this->assertSame('Témoin B', $foreignTestimonial->fresh()->name);
        $this->assertSame(0, SchoolPublicTestimonial::withoutGlobalScopes()->where('school_id', $schoolA->id)->count());
        $this->assertSame(0, SchoolPublicGalleryItem::withoutGlobalScopes()->where('school_id', $schoolA->id)->count());
        Storage::disk('public')->assertMissing($galleryPath);
        $this->assertSame($admin->school_id, $schoolA->id);
    }
}
