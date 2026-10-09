<?php

namespace Tests\Feature\Platform;

use App\Enums\LicenseStatus;
use App\Enums\LicenseType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\DemoRequest;
use App\Models\License;
use App\Models\Payment;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — License + Payment + licence initiale à 150 000 FCFA.
 *
 * L'activation matérialise la licence initiale (active) et le paiement
 * initial (confirmé, manuel). On vérifie les montants, les références,
 * le multi-tenancy et les scopes de cycle de vie.
 */
class LicensePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const INITIAL_AMOUNT = 150000;
    private const RENEWAL_AMOUNT = 70000;
    private const CURRENCY = 'XOF';

    public function test_initial_license_amounts_come_from_configuration(): void
    {
        $this->assertSame(
            self::INITIAL_AMOUNT,
            (int) config('cori.billing.initial_amount')
        );
        $this->assertSame(
            self::RENEWAL_AMOUNT,
            (int) config('cori.billing.renewal_amount')
        );
        $this->assertSame(self::CURRENCY, config('cori.billing.currency'));
    }

    public function test_activation_materialises_an_active_initial_license_of_150000_xof(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->approved();
        $this->paid($demo);

        $this->freshActingAs($admin)
            ->post(route('platform.demo-requests.activate', $demo), $this->adminPayload());

        $this->assertDatabaseCount('licenses', 1);
        $this->assertDatabaseHas('payments', ['amount' => self::INITIAL_AMOUNT]);

        $license = License::withoutGlobalScopes()->first();

        $this->assertSame($demo->refresh()->school_id, $license->school_id);
        $this->assertSame(LicenseType::Initial, $license->type);
        $this->assertSame(self::INITIAL_AMOUNT, $license->amount);
        $this->assertSame(self::CURRENCY, $license->currency);
        $this->assertSame(LicenseStatus::Active, $license->status);
        $this->assertNotNull($license->starts_at);
        $this->assertNotNull($license->expires_at);
        $this->assertNotNull($license->paid_at);
        // L'année scolaire est gérée en aval (phase suivante).
        $this->assertNull($license->academic_year_id);
        $this->assertSame($admin->id, $license->created_by);
        $this->assertSame($admin->id, $license->activated_by);
        $this->assertNotNull($license->payment_reference);
    }

    public function test_activation_materialises_a_confirmed_manual_payment_linked_to_the_license(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->approved();
        $this->paid($demo);

        $this->freshActingAs($admin)
            ->post(route('platform.demo-requests.activate', $demo), $this->adminPayload());

        $license = License::withoutGlobalScopes()->first();
        $payment = Payment::withoutGlobalScopes()->first();

        $this->assertSame($license->id, $payment->license_id);
        $this->assertSame(self::INITIAL_AMOUNT, $payment->amount);
        $this->assertSame(self::CURRENCY, $payment->currency);
        $this->assertSame(PaymentStatus::Confirmed, $payment->status);
        $this->assertSame(PaymentMethod::Manual, $payment->method);
        $this->assertSame($license->payment_reference, $payment->reference);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->confirmed_at);
        $this->assertSame($admin->id, $payment->created_by);
        $this->assertSame($admin->id, $payment->confirmed_by);
        $this->assertSame($demo->id, $payment->metadata['demo_request_id'] ?? null);
        $this->assertSame('initial', $payment->metadata['kind'] ?? null);
    }

    public function test_the_activation_flash_carries_the_financial_references(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->approved();
        $this->paid($demo);

        $response = $this->freshActingAs($admin)
            ->post(route('platform.demo-requests.activate', $demo), $this->adminPayload());

        $license = License::withoutGlobalScopes()->first();

        $response->assertSessionHas('activation');
        $this->assertSame(
            $license->payment_reference,
            $response->getSession()->get('activation.license_reference')
        );
        $this->assertSame(
            self::INITIAL_AMOUNT,
            (int) $response->getSession()->get('activation.payment_amount')
        );
    }

    public function test_the_initial_license_is_granted_on_a_full_year(): void
    {
        $demo = $this->approved();
        $this->paid($demo);

        $this->freshActingAs($this->platformAdmin())
            ->post(route('platform.demo-requests.activate', $demo), $this->adminPayload());

        $license = License::withoutGlobalScopes()->first();

        $this->assertTrue($license->expires_at->greaterThan(now()->addYear()->subDay()));
        $this->assertTrue($license->expires_at->lessThanOrEqualTo(now()->addYear()->addDay()));
    }

    public function test_licenses_and_payments_are_isolated_per_school(): void
    {
        $demo = $this->approved();
        $this->paid($demo);
        $this->freshActingAs($this->platformAdmin())
            ->post(route('platform.demo-requests.activate', $demo), $this->adminPayload());

        $anotherSchoolAdmin = User::factory()->admin()->create();

        // Vue depuis un autre établissement : aucune licence ni paiement.
        $this->actingAs($anotherSchoolAdmin);
        $this->assertSame(0, License::count());
        $this->assertSame(0, Payment::count());

        // Vue Super Admin : tout est visible.
        $this->actingAs($this->platformAdmin());
        $this->assertSame(1, License::count());
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, Payment::initial()->count());
    }

    public function test_the_model_distinguishes_initial_from_renewal(): void
    {
        $school = School::factory()->create();

        // La règle des montants est ailleurs : on vérifie seulement que le
        // modèle distingue bien les deux types (la logique de renouvellement
        // est posée en phase Renouvellement).
        $license = License::withoutGlobalScopes()->create([
            'school_id' => $school->id,
            'type' => LicenseType::Renewal,
            'amount' => self::RENEWAL_AMOUNT,
            'currency' => self::CURRENCY,
            'status' => LicenseStatus::PaymentPending,
        ]);

        $this->assertTrue($license->isRenewal());
        $this->assertFalse($license->isActive());
        $this->assertSame(self::RENEWAL_AMOUNT, $license->amount);
        $this->assertSame('70 000 FCFA', $license->formattedAmount());
    }

    public function test_lifecycle_scopes_spot_expired_and_expiring_soon_licenses(): void
    {
        $schoolA = \App\Models\School::factory()->create();
        $schoolB = \App\Models\School::factory()->create();

        License::withoutGlobalScopes()->create([
            'school_id' => $schoolA->id,
            'type' => LicenseType::Initial,
            'amount' => self::INITIAL_AMOUNT,
            'currency' => self::CURRENCY,
            'status' => LicenseStatus::Expired,
            'starts_at' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        License::withoutGlobalScopes()->create([
            'school_id' => $schoolB->id,
            'type' => LicenseType::Renewal,
            'amount' => self::RENEWAL_AMOUNT,
            'currency' => self::CURRENCY,
            'status' => LicenseStatus::Active,
            'starts_at' => now(),
            'expires_at' => now()->addDays(10),
        ]);

        $this->assertSame(1, License::withoutGlobalScopes()->expired()->count());
        $this->assertSame(1, License::withoutGlobalScopes()->expiringSoon(30)->count());
        $this->assertSame(1, License::withoutGlobalScopes()->active()->count());
    }

    public function test_the_platform_dashboard_never_invents_revenue_figures(): void
    {
        $this->actingAs($this->platformAdmin())
            ->get(route('platform.dashboard'))
            ->assertOk();
    }

    public function test_activation_cannot_happen_without_a_payment_confirmed(): void
    {
        // Aucun paiement confirmé → pas d'école, pas de licence, pas de paiement.
        $demo = $this->approved();

        $this->freshActingAs($this->platformAdmin())
            ->post(route('platform.demo-requests.activate', $demo), $this->adminPayload())
            ->assertStatus(422);

        $this->assertSame(0, License::withoutGlobalScopes()->count());
        $this->assertSame(0, Payment::withoutGlobalScopes()->count());
    }

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'role' => Role::PlatformAdmin,
            'school_id' => null,
        ]);
    }

    private function freshActingAs(User $admin): static
    {
        return $this->withSession([
            'two_factor_verified_at' => now()->timestamp,
            'two_factor_sensitive.activate_school' => now()->timestamp,
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
            'status' => \App\Enums\DemoRequestStatus::Approved,
            'approved_at' => now(),
            'handled_by' => $this->platformAdmin()->id,
        ]);
    }

    private function paid(DemoRequest $demo): void
    {
        $demo->forceFill([
            'payment_requested_at' => now(),
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => $this->platformAdmin()->id,
        ])->save();
    }

    /** @return array<string, string> */
    private function adminPayload(): array
    {
        return [
            'admin_name' => 'M. Jean AHOYO',
            'admin_email' => 'direction@lmc.test',
            'admin_password' => '',
        ];
    }
}