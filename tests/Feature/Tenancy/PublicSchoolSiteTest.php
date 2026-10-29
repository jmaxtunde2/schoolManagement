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
                ->missing('school.options')
                ->missing('support'));

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
            'options' => [
                'public_site' => [
                    'results_headline' => '92 % de réussite aux examens nationaux',
                    'results_note' => 'Session 2025',
                ],
            ],
        ]);

        $this->get(route('public.school.slug', $school->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('school.name', 'École sans domaine')
                ->where('school.slogan', 'Un accès public par slug.')
                ->where('school.results_headline', '92 % de réussite aux examens nationaux')
                ->where('school.results_note', 'Session 2025')
                ->where('school.canonical_url', route('public.school.slug', $school->slug)));

        $this->get('/ecole/slug-inexistant')->assertNotFound();
    }

    public function test_authenticated_user_can_preview_a_public_school_site(): void
    {
        $school = School::factory()->create(['name' => 'École Aperçu']);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => $school->name,
        ]);
        $user = User::factory()->admin()->forSchool($school)->create();
        $url = route('public.school.slug', $school->slug);

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route($user->role->homeRoute()));

        $this->actingAs($user)
            ->get($url.'?preview=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('school.name', 'École Aperçu')
                ->where('preview', true)
                ->where('preview_dashboard_url', route($user->role->homeRoute())));
    }

    public function test_authenticated_user_can_preview_the_platform_landing(): void
    {
        config([
            'cori.support.whatsapp' => '+2250700000000',
            'cori.support.whatsapp_message' => 'Bonjour CoriSchool',
        ]);

        $user = User::factory()->admin()
            ->forSchool(School::factory()->create())
            ->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route($user->role->homeRoute()));

        $this->actingAs($user)
            ->get('/?preview=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/CoriSchoolLanding')
                ->where('support.whatsapp', '+2250700000000')
                ->where('support.whatsapp_message', 'Bonjour CoriSchool'));
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
            'results_headline' => '92 % de réussite aux examens nationaux',
            'results_note' => 'Session 2025',
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1000, 600),
        ])->assertSessionHasNoErrors();

        $settings = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $schoolA->id)
            ->firstOrFail();
        $this->assertSame('Former les citoyens de demain.', data_get($settings->options, 'public_site.mission'));
        $this->assertSame('92 % de réussite aux examens nationaux', data_get($settings->options, 'public_site.results_headline'));
        $this->assertSame('Session 2025', data_get($settings->options, 'public_site.results_note'));
        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $schoolA->id,
            'action' => 'settings.updated',
        ]);
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

    public function test_school_admin_can_save_multiple_palmares(): void
    {
        $school = School::factory()->create(['name' => 'École Palmarès']);
        $admin = $this->verifiedAdmin($school);

        $this->put('/admin/settings/public-site', [
            'results' => [
                ['title' => '92 % de réussite au BAC', 'note' => 'Session 2025'],
                ['title' => '3 mentions très bien', 'note' => 'Session 2024'],
            ],
        ])->assertSessionHasNoErrors();

        $settings = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->firstOrFail();

        $this->assertSame([
            ['title' => '92 % de réussite au BAC', 'note' => 'Session 2025'],
            ['title' => '3 mentions très bien', 'note' => 'Session 2024'],
        ], data_get($settings->options, 'public_site.results'));

        $this->get('/admin/settings/public-site')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/PublicSite')
                ->has('publicSite.results', 2)
                ->where('publicSite.results.0.title', '92 % de réussite au BAC'));

        $this->get(route('public.school.slug', $school->slug).'?preview=1')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->has('school.results', 2)
                ->where('school.results.1.note', 'Session 2024'));
    }

    public function test_public_site_exposes_tuition_curriculum_and_calendar(): void
    {
        $school = School::factory()->create(['name' => 'École Complète']);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => $school->name,
            'options' => [
                'public_site' => [
                    'tuition_currency' => 'FCFA',
                    'tuition_note' => 'Paiement en trois tranches possible.',
                    'tuition_fees' => [
                        ['label' => "Frais d'inscription", 'amount' => '25 000', 'note' => 'Non remboursable'],
                        ['label' => 'Scolarité mensuelle', 'amount' => '30 000', 'note' => null],
                        ['label' => '', 'amount' => '999', 'note' => 'ligne vide ignorée'],
                    ],
                    'curriculum_intro' => 'Un programme conforme au référentiel national.',
                    'curriculum' => [
                        ['title' => 'Primaire', 'description' => 'Français, mathématiques, sciences.'],
                        ['title' => '', 'description' => 'ignoré'],
                    ],
                    'calendar_intro' => 'Grandes dates de l’année scolaire.',
                    'calendar' => [
                        ['label' => 'Rentrée des classes', 'date' => 'Lundi 2 octobre 2026', 'description' => 'Accueil à 7h30'],
                        ['label' => '', 'date' => 'ligne vide ignorée'],
                    ],
                ],
            ],
        ]);

        $this->get(route('public.school.slug', $school->slug))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/SchoolLanding')
                ->where('tuition.currency', 'FCFA')
                ->where('tuition.note', 'Paiement en trois tranches possible.')
                ->has('tuition.fees', 2)
                ->where('tuition.fees.0.label', "Frais d'inscription")
                ->where('tuition.fees.1.label', 'Scolarité mensuelle')
                ->where('curriculum.intro', 'Un programme conforme au référentiel national.')
                ->has('curriculum.items', 1)
                ->where('curriculum.items.0.title', 'Primaire')
                ->where('calendar.intro', 'Grandes dates de l’année scolaire.')
                ->has('calendar.items', 1)
                ->where('calendar.items.0.label', 'Rentrée des classes')
                ->where('calendar.items.0.date', 'Lundi 2 octobre 2026'));
    }

    public function test_school_admin_can_save_tuition_curriculum_and_calendar(): void
    {
        $school = School::factory()->create(['name' => 'École Sauvegarde']);
        $this->verifiedAdmin($school);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => $school->name,
        ]);

        $this->put('/admin/settings/public-site', [
            'mission' => 'Mission.',
            'tuition_currency' => 'FCFA',
            'tuition_note' => 'Note frais.',
            'tuition_fees' => [
                ['label' => "Frais d'inscription", 'amount' => '25000', 'note' => 'À l’inscription'],
            ],
            'curriculum_intro' => 'Intro programme.',
            'curriculum' => [
                ['title' => 'Primaire', 'description' => 'Description.'],
            ],
            'calendar_intro' => 'Intro calendrier.',
            'calendar' => [
                ['label' => 'Rentrée', 'date' => '2 octobre 2026', 'description' => 'Accueil'],
            ],
        ])->assertSessionHasNoErrors();

        $settings = SchoolSetting::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->firstOrFail();

        $this->assertSame('FCFA', data_get($settings->options, 'public_site.tuition_currency'));
        $this->assertCount(1, data_get($settings->options, 'public_site.tuition_fees'));
        $this->assertSame("Frais d'inscription", data_get($settings->options, 'public_site.tuition_fees.0.label'));
        $this->assertCount(1, data_get($settings->options, 'public_site.curriculum'));
        $this->assertSame('Primaire', data_get($settings->options, 'public_site.curriculum.0.title'));
        $this->assertCount(1, data_get($settings->options, 'public_site.calendar'));
        $this->assertSame('Rentrée', data_get($settings->options, 'public_site.calendar.0.label'));
    }

    public function test_public_site_rejects_too_many_fee_lines(): void
    {
        $school = School::factory()->create(['name' => 'École Limites']);
        $this->verifiedAdmin($school);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => $school->name,
        ]);

        $fees = array_fill(0, 11, ['label' => 'Frais', 'amount' => '1', 'note' => null]);

        $this->put('/admin/settings/public-site', [
            'tuition_fees' => $fees,
        ])->assertSessionHasErrors('tuition_fees');
    }
}
