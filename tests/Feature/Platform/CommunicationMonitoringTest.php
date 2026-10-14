<?php

namespace Tests\Feature\Platform;

use App\Enums\Role;
use App\Models\Notification;
use App\Models\ParentGuardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\Communication\CommunicationSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommunicationMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'role' => Role::PlatformAdmin,
            'school_id' => null,
        ]);
    }

    private function notification(School $school, string $status = 'sent'): Notification
    {
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = ParentGuardian::factory()->create(['school_id' => $school->id, 'phone' => '+22890000001']);

        return Notification::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'parent_id' => $guardian->id,
            'channel' => 'sms',
            'phone' => '+22890000001',
            'message' => 'Nouvelles notes disponibles.',
            'status' => $status,
            'estimated_cost' => 25,
            'cost_currency' => 'XOF',
        ]);
    }

    public function test_platform_admin_sees_communication_kpis_and_school_table(): void
    {
        $school = School::factory()->create(['name' => 'École A']);
        app(CommunicationSettingsService::class)->for($school);
        $this->notification($school, 'sent');
        $this->notification($school, 'failed');

        $this->actingAs($this->platformAdmin())
            ->get(route('platform.communication.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Communication/Index')
                ->where('kpis.total_schools', 1)
                ->where('kpis.sms_budget', 3000)
                ->where('kpis.messages_sent', 1)
                ->where('kpis.messages_failed', 1)
                ->has('schools.data', 1)
                ->where('schools.data.0.name', 'École A')
                ->where('schools.data.0.messages_sent', 1)
                ->where('schools.data.0.messages_failed', 1)
            );
    }

    public function test_platform_admin_can_open_communication_history(): void
    {
        $school = School::factory()->create();
        $this->notification($school, 'failed');

        $this->actingAs($this->platformAdmin())
            ->get(route('platform.communication.history'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Platform/Communication/History')
                ->has('notifications.data', 1)
                ->where('notifications.data.0.status', 'failed')
            );
    }

    public function test_a_school_admin_cannot_access_platform_communication_monitoring(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->admin()->forSchool($school)->create();

        $this->actingAs($admin)
            ->get(route('platform.communication.index'))
            ->assertForbidden();
    }
}
