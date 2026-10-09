<?php

namespace Tests\Feature\Platform;

use App\Enums\DemoRequestStatus;
use App\Enums\Role;
use App\Models\DemoRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Phase 3 — Approbation → processus d'activation.
 *
 * Le Super Admin déroule : paiement initial demandé (150 000 FCFA), réception
 * confirmée manuellement, puis activation atomique de l'école et provisionnement
 * du compte administrateur. Chaque action sensible exige une 2FA récente.
 */
class DemoActivationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => Role::PlatformAdmin,
            'school_id' => null,
        ]);
    }

    /**
     * Session 2FA vérifiée + action sensible revalidée récemment.
     */
    private function actingAsFresh(User $admin, string $purpose): static
    {
        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
            'two_factor_sensitive.'.$purpose => now()->timestamp,
        ])->actingAs($admin);
    }

    private function approved(): DemoRequest
    {
        return DemoRequest::create([
            'school_name' => 'Lycée Moderne de Cotonou',
            'address' => 'Rue des Écoles, Cotonou',
            'phone' => '+229 01 97 00 00 00',
            'email' => 'direction@lmc.test',
            'preferred_demo_date' => now()->addDays(7)->format('Y-m-d'),
            'preferred_demo_time' => '14:30',
            'status' => DemoRequestStatus::Approved,
            'approved_at' => now(),
            'handled_by' => $this->admin()->id,
        ]);
    }

    public function test_each_sensitive_action_requires_a_fresh_sensitive_verification(): void
    {
        $demo = $this->approved();

        $response = $this->actingAs($this->admin())
            ->post(route('platform.demo-requests.payment.request', $demo));

        $response->assertRedirect();
        $this->assertStringContainsString(
            'two-factor/challenge?purpose=request_payment',
            $response->headers->get('Location')
        );

        $this->assertNull($demo->fresh()->payment_requested_at);
    }

    public function test_payment_is_requested_only_for_an_approved_request(): void
    {
        $demo = $this->approved();

        $this->actingAsFresh($this->admin(), 'request_payment')
            ->post(route('platform.demo-requests.payment.request', $demo))
            ->assertSessionHas('success');

        $demo->refresh();

        $this->assertNotNull($demo->payment_requested_at);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'demo_request.payment_requested',
            'auditable_type' => DemoRequest::class,
            'auditable_id' => $demo->id,
        ]);
    }

    public function test_payment_cannot_be_requested_before_approval(): void
    {
        $demo = DemoRequest::create([
            'school_name' => 'Collège Notre-Dame',
            'address' => 'Cotonou',
            'phone' => '+229 00 00 00 00',
            'email' => 'contact@cnd.test',
            'preferred_demo_date' => now()->addDays(7)->format('Y-m-d'),
            'preferred_demo_time' => '14:30',
            'status' => DemoRequestStatus::Contacted,
        ]);

        $this->actingAsFresh($this->admin(), 'request_payment')
            ->post(route('platform.demo-requests.payment.request', $demo))
            ->assertStatus(422);

        $this->assertNull($demo->fresh()->payment_requested_at);
    }

    public function test_payment_confirmation_is_manual_and_audited(): void
    {
        $admin = $this->admin();
        $demo = $this->approved();

        $this->actingAsFresh($admin, 'request_payment')
            ->post(route('platform.demo-requests.payment.request', $demo));

        $this->actingAsFresh($admin, 'confirm_payment')
            ->post(route('platform.demo-requests.payment.confirm', $demo->refresh()))
            ->assertSessionHas('success');

        $demo->refresh();

        $this->assertNotNull($demo->payment_confirmed_at);
        $this->assertSame($admin->id, $demo->payment_confirmed_by);
        $this->assertTrue($demo->canBeActivated());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'demo_request.payment_confirmed',
            'auditable_id' => $demo->id,
        ]);
    }

    public function test_payment_cannot_be_confirmed_before_being_requested(): void
    {
        $demo = $this->approved();

        $this->actingAsFresh($this->admin(), 'confirm_payment')
            ->post(route('platform.demo-requests.payment.confirm', $demo))
            ->assertStatus(422);

        $this->assertNull($demo->fresh()->payment_confirmed_at);
    }

    public function test_activation_creates_school_setting_and_admin_account(): void
    {
        $demo = $this->approved();
        $this->markAsPaid($demo);

        $response = $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => 'M. Jean AHOYO',
                'admin_email' => 'direction@lmc.test',
                'admin_password' => '',
            ]);

        $response->assertRedirect(route('platform.demo-requests.show', $demo));
        $response->assertSessionHas('activation');

        $demo->refresh();

        $this->assertNotNull($demo->activated_at);
        $this->assertNotNull($demo->school_id);

        $school = School::withoutGlobalScopes()->find($demo->school_id);
        $this->assertNotNull($school);
        $this->assertTrue($school->is_active);

        $this->assertDatabaseHas('school_settings', [
            'school_id' => $school->id,
            'school_name' => $demo->school_name,
            'address' => $demo->address,
            'phone' => $demo->phone,
            'email' => $demo->email,
        ]);

        $adminUser = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('email', 'direction@lmc.test')
            ->first();

        $this->assertNotNull($adminUser);
        $this->assertSame(Role::Admin, $adminUser->role);
        $this->assertTrue($adminUser->is_active);

        // Le mot de passe envoyé au front (généré) est différent du hash stocké.
        $this->assertNotEquals('', $response->getSession()->get('activation.temporary_password'));
        $this->assertNotEquals(
            $response->getSession()->get('activation.temporary_password'),
            $adminUser->password
        );
    }

    public function test_activation_uses_the_provided_password_when_given(): void
    {
        $demo = $this->approved();
        $this->markAsPaid($demo);

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => 'Mme Mariam SOULE',
                'admin_email' => 'direction@lmc.test',
                'admin_password' => 'motdepasse-fort-9',
            ])
            ->assertSessionHasNoErrors();

        $adminUser = User::withoutGlobalScopes()
            ->where('email', 'direction@lmc.test')
            ->first();

        $this->assertTrue(
            Hash::check('motdepasse-fort-9', $adminUser->password)
        );
    }

    public function test_activation_is_refused_as_long_as_the_payment_is_not_confirmed(): void
    {
        $demo = $this->approved();

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => 'M. Jean AHOYO',
                'admin_email' => 'direction@lmc.test',
            ])
            ->assertStatus(422);

        $this->assertNull($demo->fresh()->activated_at);
        $this->assertSame(0, School::withoutGlobalScopes()->count());
    }

    public function test_activation_is_refused_twice(): void
    {
        $demo = $this->approved();
        $this->markAsPaid($demo);

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => 'M. Jean AHOYO',
                'admin_email' => 'direction@lmc.test',
            ])
            ->assertSessionHas('activation');

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo->refresh()), [
                'admin_name' => 'M. Jean AHOYO',
                'admin_email' => 'direction2@lmc.test',
            ])
            ->assertStatus(422);

        $this->assertSame(1, School::withoutGlobalScopes()->count());
    }

    public function test_activation_fields_are_validated(): void
    {
        $demo = $this->approved();
        $this->markAsPaid($demo);

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => '',
                'admin_email' => 'pas-un-email',
                'admin_password' => 'petit',
            ])
            ->assertSessionHasErrors([
                'admin_name',
                'admin_email',
                'admin_password',
            ]);

        $this->assertSame(0, School::withoutGlobalScopes()->count());
    }

    public function test_activation_rejects_an_existing_email(): void
    {
        User::factory()->create(['email' => 'direction@lmc.test']);

        $demo = $this->approved();
        $this->markAsPaid($demo);

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => 'M. Jean AHOYO',
                'admin_email' => 'direction@lmc.test',
            ])
            ->assertSessionHasErrors('admin_email');

        $this->assertFalse(
            School::withoutGlobalScopes()->where('name', 'Lycée Moderne de Cotonou')->exists()
        );
    }

    public function test_activation_is_multi_tenant_isolated(): void
    {
        $existing = School::factory()->create();
        $demo = $this->approved();
        $this->markAsPaid($demo);

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => 'M. Jean AHOYO',
                'admin_email' => 'direction@lmc.test',
            ])
            ->assertSessionHas('activation');

        $adminUser = User::withoutGlobalScopes()->where('email', 'direction@lmc.test')->first();

        $this->assertNotEquals($existing->id, $adminUser->school_id);
    }

    public function test_after_activation_the_show_page_exposes_the_result(): void
    {
        $demo = $this->approved();
        $this->markAsPaid($demo);

        $this->actingAsFresh($this->admin(), 'activate_school')
            ->post(route('platform.demo-requests.activate', $demo), [
                'admin_name' => 'M. Jean AHOYO',
                'admin_email' => 'direction@lmc.test',
            ]);

        $this->actingAs($this->admin())
            ->get(route('platform.demo-requests.show', $demo->refresh()))
            ->assertInertia(fn ($page) => $page
                ->where('activation.isActivated', true)
                ->where('activation.result.school_id', $demo->school_id)
            );
    }

    private function markAsPaid(DemoRequest $demo): void
    {
        $demo->forceFill([
            'payment_requested_at' => now(),
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => $this->admin()->id,
        ])->save();
    }
}
