<?php

namespace Tests\Feature\DemoRequests;

use App\Enums\DemoRequestStatus;
use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\DemoRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Formulaire public « Demander une démonstration ».
 *
 * Règle métier : soumettre la demande crée UNIQUEMENT une `demo_requests`
 * au statut `pending` — jamais d'école, de compte, de licence ni de paiement.
 */
class DemoRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'school_name' => 'Lycée Moderne de Cotonou',
            'address' => 'Rue des Écoles, Cotonou',
            'phone' => '+229 01 97 00 00 00',
            'email' => 'direction@lmc.test',
            'preferred_demo_date' => now()->addDays(7)->format('Y-m-d'),
            'preferred_demo_time' => '14:30',
        ], $overrides);
    }

    public function test_the_demo_request_form_is_reachable_by_a_guest(): void
    {
        $response = $this->get(route('demo.request'));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/DemoRequest')
        );
    }

    public function test_a_valid_submission_creates_only_a_pending_demo_request(): void
    {
        $response = $this->post(route('demo.request.store'), $this->payload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('demo.request'));
        $response->assertSessionHas('success');

        $request = DemoRequest::firstOrFail();

        $this->assertSame(DemoRequestStatus::Pending, $request->status);
        $this->assertSame('Lycée Moderne de Cotonou', $request->school_name);
        $this->assertSame('Rue des Écoles, Cotonou', $request->address);
        $this->assertSame('+229 01 97 00 00 00', $request->phone);
        $this->assertSame('direction@lmc.test', $request->email);
        $this->assertNull($request->handled_by);
        $this->assertNull($request->internal_notes);

        // Aucune étape du workflow n'est encore atteinte.
        $this->assertNull($request->contacted_at);
        $this->assertNull($request->scheduled_at);
        $this->assertNull($request->demo_done_at);
        $this->assertNull($request->approved_at);
        $this->assertNull($request->rejected_at);
        $this->assertNull($request->cancelled_at);
    }

    public function test_a_submission_creates_no_school_and_no_user(): void
    {
        $this->post(route('demo.request.store'), $this->payload());

        $this->assertDatabaseCount('demo_requests', 1);

        $this->assertSame(0, School::withoutGlobalScopes()->count());
        $this->assertSame(0, User::withoutGlobalScopes()->count());
        $this->assertSame(0, AcademicYear::withoutGlobalScopes()->count());
        $this->assertFalse(auth()->check());
    }

    public function test_the_email_is_normalised(): void
    {
        $this->post(route('demo.request.store'), $this->payload([
            'email' => '  Direction@LMC.TEST ',
        ]));

        $this->assertSame('direction@lmc.test', DemoRequest::firstOrFail()->email);
    }

    public function test_the_form_requires_all_six_public_fields(): void
    {
        foreach ([
            'school_name',
            'address',
            'phone',
            'email',
            'preferred_demo_date',
            'preferred_demo_time',
        ] as $field) {
            $this->post(route('demo.request.store'), $this->payload([
                $field => '',
            ]))->assertSessionHasErrors($field);
        }

        $this->assertDatabaseCount('demo_requests', 0);
    }

    public function test_an_email_is_required_and_valid(): void
    {
        $this->post(route('demo.request.store'), $this->payload([
            'email' => 'pas-un-email',
        ]))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('demo_requests', 0);
    }

    public function test_a_phone_number_must_look_like_a_phone_number(): void
    {
        $this->post(route('demo.request.store'), $this->payload([
            'phone' => 'abc',
        ]))->assertSessionHasErrors('phone');

        $this->assertDatabaseCount('demo_requests', 0);
    }

    public function test_a_demo_date_in_the_past_is_rejected(): void
    {
        $this->post(route('demo.request.store'), $this->payload([
            'preferred_demo_date' => now()->subDay()->format('Y-m-d'),
        ]))->assertSessionHasErrors('preferred_demo_date');

        $this->assertDatabaseCount('demo_requests', 0);
    }

    public function test_a_malformed_demo_time_is_rejected(): void
    {
        $this->post(route('demo.request.store'), $this->payload([
            'preferred_demo_time' => 'matin',
        ]))->assertSessionHasErrors('preferred_demo_time');

        $this->assertDatabaseCount('demo_requests', 0);
    }

    public function test_a_status_sent_by_the_client_is_ignored(): void
    {
        $this->post(route('demo.request.store'), $this->payload([
            'status' => 'approved',
            'approved_at' => now()->toDateTimeString(),
            'handled_by' => 999,
        ]));

        $request = DemoRequest::firstOrFail();

        // Le statut est toujours celui de la soumission publique.
        $this->assertSame(DemoRequestStatus::Pending, $request->status);
        $this->assertNull($request->approved_at);
        $this->assertNull($request->handled_by);
    }

    public function test_a_school_id_sent_by_the_client_is_ignored(): void
    {
        $other = School::factory()->create();

        $this->post(route('demo.request.store'), $this->payload([
            'school_id' => $other->id,
        ]));

        $this->assertDatabaseCount('demo_requests', 1);
        // Aucune école créée, et la demande reste hors tenant.
        $this->assertSame(1, School::withoutGlobalScopes()->count());
        $this->assertNull(DemoRequest::firstOrFail()->school_id);
    }

    public function test_an_authenticated_user_cannot_reach_the_demo_request_form(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('demo.request'))
            ->assertRedirect(route(Role::Admin->homeRoute()));
    }

    public function test_a_submission_is_throttled_to_prevent_spam(): void
    {
        // `throttle:10,1` sur la route de soumission.
        $route = collect(
            app('router')->getRoutes()->getRoutes()
        )->first(fn ($route) => $route->getName() === 'demo.request.store');

        $this->assertContains(
            'throttle:10,1',
            $route->gatherMiddleware()
        );
    }
}
