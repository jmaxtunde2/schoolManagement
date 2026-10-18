<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class AuditJournalTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedAdmin(School $school): User
    {
        $admin = User::factory()->admin()
            ->forSchool($school)
            ->create(['two_factor_confirmed_at' => now()]);

        $this->actingAs($admin)->withSession([
            'two_factor_verified_at' => now()->timestamp,
        ]);

        return $admin;
    }

    public function test_school_admin_sees_only_its_school_activity(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        AuditLog::create([
            'school_id' => $schoolA->id,
            'action' => 'expense.created',
            'metadata' => ['amount' => 1000],
        ]);
        AuditLog::create([
            'school_id' => $schoolB->id,
            'action' => 'expense.approved',
            'metadata' => ['amount' => 2000],
        ]);

        $this->verifiedAdmin($schoolA);

        $this->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLogs/Index')
                ->where('logs.total', 1)
                ->where('logs.data.0.action', 'expense.created'));
    }

    public function test_school_admin_cannot_open_another_school_log(): void
    {
        $schoolA = School::factory()->create();
        $schoolB = School::factory()->create();

        $foreignLog = AuditLog::create([
            'school_id' => $schoolB->id,
            'action' => 'salary.saved',
        ]);

        $this->verifiedAdmin($schoolA);

        $this->get(route('admin.audit-logs.show', $foreignLog->id))
            ->assertNotFound();
    }

    public function test_school_admin_can_open_its_own_log_detail(): void
    {
        $school = School::factory()->create();
        $log = AuditLog::create([
            'school_id' => $school->id,
            'action' => 'report_card.published',
            'auditable_type' => 'App\\Models\\ReportCard',
            'auditable_id' => 42,
            'metadata' => ['version' => 2],
        ]);

        $this->verifiedAdmin($school);

        $this->get(route('admin.audit-logs.show', $log->id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AuditLogs/Show')
                ->where('log.action', 'report_card.published')
                ->where('log.auditable_id', 42)
                ->where('log.metadata.version', 2));
    }

    public function test_audit_logs_are_immutable(): void
    {
        $school = School::factory()->create();
        $log = AuditLog::create([
            'school_id' => $school->id,
            'action' => 'user.created',
        ]);

        $this->expectException(RuntimeException::class);

        $log->update(['action' => 'hacked']);
    }

    public function test_audit_logs_cannot_be_deleted(): void
    {
        $school = School::factory()->create();
        $log = AuditLog::create([
            'school_id' => $school->id,
            'action' => 'user.created',
        ]);

        $this->expectException(RuntimeException::class);

        $log->delete();
    }

    public function test_audit_service_records_the_authenticated_context(): void
    {
        $school = School::factory()->create();
        $admin = $this->verifiedAdmin($school);

        app(AuditService::class)->log('configuration.updated', null, ['key' => 'branding']);

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'action' => 'configuration.updated',
        ]);

        $log = AuditLog::query()->firstOrFail();
        $this->assertSame('branding', $log->metadata['key']);
    }

    public function test_updating_school_settings_is_audited(): void
    {
        $school = School::factory()->create();
        $admin = $this->verifiedAdmin($school);

        $this->put(route('admin.settings.update'), [
            'school_name' => 'École mise à jour',
            'primary_color' => '#14532D',
            'secondary_color' => '#0F766E',
            'accent_color' => '#B45309',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('audit_logs', [
            'school_id' => $school->id,
            'user_id' => $admin->id,
            'action' => 'settings.updated',
        ]);
    }
}
