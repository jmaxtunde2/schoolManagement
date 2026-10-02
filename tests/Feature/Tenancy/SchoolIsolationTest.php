<?php

namespace Tests\Feature\Tenancy;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SchoolIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function schoolWithSettings(string $name, string $color): School
    {
        $school = School::factory()->create(['name' => $name]);
        SchoolSetting::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'school_name' => $name,
            'primary_color' => $color,
        ]);

        return $school;
    }

    public function test_global_scope_only_returns_rows_of_the_authenticated_users_school(): void
    {
        $a = $this->schoolWithSettings('Ecole A', '#111111');
        $b = $this->schoolWithSettings('Ecole B', '#222222');

        $this->actingAs(User::factory()->admin()->forSchool($a)->create());

        $this->assertSame(1, SchoolSetting::count());
        $this->assertSame($a->id, SchoolSetting::first()->school_id);
        $this->assertNull(SchoolSetting::where('school_id', $b->id)->first());
    }

    public function test_school_id_is_filled_automatically_on_creation(): void
    {
        $a = School::factory()->create();
        $this->actingAs(User::factory()->admin()->forSchool($a)->create());

        $setting = SchoolSetting::create(['school_name' => 'Auto']);

        $this->assertSame($a->id, $setting->school_id);
    }

    public function test_each_school_receives_its_own_branding(): void
    {
        $a = $this->schoolWithSettings('Ecole A', '#1E40AF');
        $b = $this->schoolWithSettings('Ecole B', '#6D28D9');

        $this->actingAs(User::factory()->admin()->forSchool($a)->create())
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('school.name', 'Ecole A')
                ->where('school.primary_color', '#1E40AF'));

        $this->actingAs(User::factory()->admin()->forSchool($b)->create())
            ->get('/admin/dashboard')
            ->assertInertia(fn (Assert $page) => $page
                ->where('school.name', 'Ecole B')
                ->where('school.primary_color', '#6D28D9'));
    }

    public function test_theme_variables_are_present_in_initial_html(): void
    {
        $a = $this->schoolWithSettings('Ecole A', '#6D28D9');

        $this->actingAs(User::factory()->admin()->forSchool($a)->create())
            ->get('/admin/dashboard')
            ->assertSee('--school-primary: #6D28D9', false);
    }

    public function test_teacher_cannot_access_admin_area(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_cannot_access_teacher_area(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/teacher/dashboard')
            ->assertForbidden();
    }

    public function test_user_without_school_is_forbidden(): void
    {
        $this->actingAs(User::factory()->admin()->withoutSchool()->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_user_of_inactive_school_is_forbidden(): void
    {
        $school = School::factory()->create(['is_active' => false]);

        $this->actingAs(User::factory()->admin()->forSchool($school)->create())
            ->get('/admin/dashboard')
            ->assertForbidden();
    }
}
