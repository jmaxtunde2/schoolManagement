<?php

namespace Tests\Feature\Communication;

use App\Models\School;
use App\Models\SchoolCommunicationSetting;
use App\Models\User;
use App\Services\Communication\CommunicationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommunicationSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function school(): School
    {
        return School::factory()->create();
    }

    public function test_admin_can_view_communication_settings(): void
    {
        $school = $this->school();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.communication'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Communication')
                ->where('settings.sms_budget', 3000)
                ->where('settings.sms_enabled', true)
            );
    }

    public function test_admin_can_update_communication_settings(): void
    {
        $school = $this->school();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.communication.update'), [
                'sms_enabled' => false,
                'whatsapp_enabled' => true,
                'email_enabled' => true,
                'sms_low_balance_threshold' => 400,
                'sms_reactivation_threshold' => 900,
                'whatsapp_low_budget_threshold' => 0.5,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $settings = SchoolCommunicationSetting::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->firstOrFail();

        $this->assertFalse($settings->sms_enabled);
        $this->assertSame(400, $settings->sms_low_balance_threshold);
        $this->assertSame(900, $settings->sms_reactivation_threshold);
    }

    public function test_reactivation_threshold_must_be_greater_than_low_threshold(): void
    {
        $school = $this->school();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->from(route('admin.settings.communication'))
            ->put(route('admin.settings.communication.update'), [
                'sms_low_balance_threshold' => 1000,
                'sms_reactivation_threshold' => 500,
                'whatsapp_low_budget_threshold' => 1,
            ])
            ->assertSessionHasErrors('sms_reactivation_threshold');
    }

    public function test_settings_are_scoped_to_the_admin_school(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        app(CommunicationSettingsService::class)->for($schoolB)->update(['sms_budget' => 9999]);

        $admin = User::factory()->admin()->forSchool($schoolA)->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.communication'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.sms_budget', 3000)
            );
    }

    public function test_teacher_cannot_access_communication_settings(): void
    {
        $school = $this->school();
        $teacher = User::factory()->teacher()->forSchool($school)->create();

        $this->actingAs($teacher)
            ->get(route('admin.settings.communication'))
            ->assertForbidden();
    }
}
