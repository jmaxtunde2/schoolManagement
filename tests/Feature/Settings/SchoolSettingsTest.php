<?php

namespace Tests\Feature\Settings;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SchoolSettingsTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->school = School::factory()->create(['name' => 'Ecole A']);
        $this->admin = User::factory()->admin()->forSchool($this->school)->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            '_method' => 'put',
            'school_name' => 'Lycée Test',
            'short_name' => 'LT',
            'slogan' => 'Apprendre',
            'primary_color' => '#123abc',
            'secondary_color' => '#0F766E',
            'accent_color' => '#D97706',
        ], $overrides);
    }

    private function settingOf(School $school): ?SchoolSetting
    {
        return SchoolSetting::withoutGlobalScopes()->where('school_id', $school->id)->first();
    }

    public function test_admin_can_view_settings_page_with_defaults(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/School')
                ->where('settings.school_name', 'Ecole A')
                ->where('settings.primary_color', '#1E40AF'));
    }

    public function test_teacher_cannot_view_or_update_settings(): void
    {
        $teacher = User::factory()->teacher()->forSchool($this->school)->create();

        $this->actingAs($teacher)->get('/admin/settings')->assertForbidden();
        $this->actingAs($teacher)->post('/admin/settings', $this->payload())->assertForbidden();

        $this->assertDatabaseCount('school_settings', 0);
    }

    public function test_admin_can_update_identity_contact_and_colors(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/settings', $this->payload([
                'city' => 'Cotonou',
                'email' => 'a@ecole.test',
                'phone' => '+229 97 00 00 00',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $setting = $this->settingOf($this->school);

        $this->assertSame('Lycée Test', $setting->school_name);
        $this->assertSame('Cotonou', $setting->city);
        $this->assertSame('#123ABC', $setting->primary_color); // normalisé en majuscules
    }

    public function test_updated_branding_is_reflected_in_shared_props(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard'); // remplit le cache avec les valeurs par défaut

        $this->actingAs($this->admin)->post('/admin/settings', $this->payload());

        $this->actingAs($this->admin)
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('school.name', 'Lycée Test')
                ->where('school.primary_color', '#123ABC'));
    }

    #[DataProvider('invalidColors')]
    public function test_invalid_colors_are_rejected(string $value): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/settings', $this->payload(['primary_color' => $value]))
            ->assertSessionHasErrors('primary_color');
    }

    public static function invalidColors(): array
    {
        return [['red'], ['#12345'], ['#GGGGGG'], ['123ABC'], ['url(javascript:alert(1))']];
    }

    public function test_school_name_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/settings', $this->payload(['school_name' => '']))
            ->assertSessionHasErrors('school_name');
    }

    public function test_admin_can_upload_a_logo(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/settings', $this->payload(['logo' => UploadedFile::fake()->image('logo.png', 300, 300)]))
            ->assertSessionHasNoErrors();

        $path = $this->settingOf($this->school)->logo_path;

        $this->assertStringStartsWith("schools/{$this->school->id}/logo/", $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replacing_the_logo_deletes_the_previous_file(): void
    {
        $this->actingAs($this->admin)->post('/admin/settings', $this->payload(['logo' => UploadedFile::fake()->image('a.png', 200, 200)]));
        $old = $this->settingOf($this->school)->logo_path;

        $this->actingAs($this->admin)->post('/admin/settings', $this->payload(['logo' => UploadedFile::fake()->image('b.jpg', 200, 200)]));
        $new = $this->settingOf($this->school)->logo_path;

        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
    }

    public function test_admin_can_remove_the_logo(): void
    {
        $this->actingAs($this->admin)->post('/admin/settings', $this->payload(['logo' => UploadedFile::fake()->image('a.png', 200, 200)]));
        $path = $this->settingOf($this->school)->logo_path;

        $this->actingAs($this->admin)->post('/admin/settings', $this->payload(['remove_logo' => true]));

        $this->assertNull($this->settingOf($this->school)->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_logo_with_invalid_type_size_or_dimensions_is_rejected(): void
    {
        $bad = [
            UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
            UploadedFile::fake()->image('big.png', 300, 300)->size(3000),
            UploadedFile::fake()->image('tiny.png', 20, 20),
            UploadedFile::fake()->image('huge.png', 2500, 2500),
        ];

        foreach ($bad as $file) {
            $this->actingAs($this->admin)
                ->post('/admin/settings', $this->payload(['logo' => $file]))
                ->assertSessionHasErrors('logo');
        }

        $this->assertDatabaseCount('school_settings', 0);
    }

    public function test_school_id_sent_by_the_client_is_ignored(): void
    {
        $other = School::factory()->create();
        SchoolSetting::withoutGlobalScopes()->create(['school_id' => $other->id, 'school_name' => 'Ecole B', 'primary_color' => '#222222']);

        $this->actingAs($this->admin)
            ->post('/admin/settings', $this->payload(['school_id' => $other->id]))
            ->assertSessionHasNoErrors();

        $untouched = $this->settingOf($other);
        $this->assertSame('Ecole B', $untouched->school_name);
        $this->assertSame('#222222', $untouched->primary_color);
        $this->assertSame('Lycée Test', $this->settingOf($this->school)->school_name);
    }

    public function test_admin_of_another_school_cannot_alter_this_school(): void
    {
        SchoolSetting::withoutGlobalScopes()->create(['school_id' => $this->school->id, 'school_name' => 'Ecole A', 'primary_color' => '#111111']);

        $this->actingAs(User::factory()->admin()->create())->post('/admin/settings', $this->payload());

        $this->assertSame('Ecole A', $this->settingOf($this->school)->school_name);
    }
}
