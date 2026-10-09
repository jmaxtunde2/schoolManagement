<?php

namespace Tests\Feature\Platform;

use App\Enums\DemoRequestStatus;
use App\Enums\Role;
use App\Models\DemoRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Espace Super Admin Cori : gestion des demandes de démonstration.
 *
 * Ces routes vivent sous `role:platform_admin` — aucun utilisateur d'école
 * ne doit pouvoir y accéder, même en forçant l'URL.
 */
class DemoRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'role' => Role::PlatformAdmin,
            'school_id' => null,
        ]);
    }

    private function demoRequest(array $overrides = []): DemoRequest
    {
        return DemoRequest::create(array_merge([
            'school_name' => 'Lycée Moderne de Cotonou',
            'address' => 'Rue des Écoles, Cotonou',
            'phone' => '+229 01 97 00 00 00',
            'email' => 'direction@lmc.test',
            'preferred_demo_date' => now()->addDays(7)->format('Y-m-d'),
            'preferred_demo_time' => '14:30',
            'status' => DemoRequestStatus::Pending,
        ], $overrides));
    }

    public function test_a_platform_admin_can_list_the_demo_requests(): void
    {
        $this->demoRequest();
        $this->demoRequest(['school_name' => 'Institut Sainte-Marie']);

        $response = $this->actingAs($this->platformAdmin())
            ->get(route('platform.demo-requests.index'));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Platform/DemoRequests/Index')
            ->has('demoRequests.data', 2)
            ->where('demoRequests.data.0.school_name', 'Institut Sainte-Marie')
            ->where('statusOptions.pending', 'En attente')
            ->where('counts.pending', 2)
        );
    }

    public function test_the_list_can_be_filtered_by_status_and_searched(): void
    {
        $this->demoRequest();
        $this->demoRequest([
            'school_name' => 'Institut Sainte-Marie',
            'status' => DemoRequestStatus::Approved,
        ]);

        $this->actingAs($this->platformAdmin())
            ->get(route('platform.demo-requests.index', ['status' => 'approved']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('demoRequests.data', 1)
                ->where('demoRequests.data.0.school_name', 'Institut Sainte-Marie')
            );

        $this->actingAs($this->platformAdmin())
            ->get(route('platform.demo-requests.index', ['search' => 'Institut']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('demoRequests.data', 1)
            );

        $this->actingAs($this->platformAdmin())
            ->get(route('platform.demo-requests.index', ['search' => 'zzz']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('demoRequests.data', 0)
            );
    }

    public function test_a_platform_admin_can_open_a_demo_request(): void
    {
        $demo = $this->demoRequest();

        $response = $this->actingAs($this->platformAdmin())
            ->get(route('platform.demo-requests.show', $demo));

        $response->assertOk();

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Platform/DemoRequests/Show')
            ->where('demoRequest.school_name', 'Lycée Moderne de Cotonou')
            ->where('demoRequest.status', 'pending')
            ->has('allowedTransitions', 3)
            ->has('history', 0)
        );
    }

    public function test_a_school_admin_cannot_reach_the_demo_request_area(): void
    {
        $admin = User::factory()->admin()->create();
        $demo = $this->demoRequest();

        $this->actingAs($admin)
            ->get(route('platform.demo-requests.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('platform.demo-requests.show', $demo))
            ->assertForbidden();
    }

    public function test_status_transitions_follow_the_workflow(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest();

        $steps = ['contacted', 'scheduled', 'demo_done', 'approved'];

        foreach ($steps as $step) {
            $response = $this->actingAs($admin)->patch(
                route('platform.demo-requests.status', $demo),
                [
                    'status' => $step,
                    'preferred_demo_date' => $demo->preferred_demo_date->format('Y-m-d'),
                    'preferred_demo_time' => '14:30',
                ]
            );

            $response->assertSessionHasNoErrors();
            $demo->refresh();

            $this->assertSame($step, $demo->status->value);
            $this->assertNotNull($demo->handled_by);
        }

        // Chaque étape est horodatée.
        $this->assertNotNull($demo->contacted_at);
        $this->assertNotNull($demo->scheduled_at);
        $this->assertNotNull($demo->demo_done_at);
        $this->assertNotNull($demo->approved_at);
        $this->assertSame($admin->id, $demo->handled_by);
    }

    public function test_an_invalid_transition_is_rejected(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest();

        // pending → approved saute toute la chaîne.
        $this->actingAs($admin)
            ->patch(route('platform.demo-requests.status', $demo), [
                'status' => 'approved',
                'preferred_demo_date' => $demo->preferred_demo_date->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(
            DemoRequestStatus::Pending,
            $demo->refresh()->status
        );
    }

    public function test_a_terminal_status_allows_no_further_transition(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest(['status' => DemoRequestStatus::Approved]);

        $response = $this->actingAs($admin)
            ->patch(route('platform.demo-requests.status', $demo), [
                'status' => 'rejected',
                'preferred_demo_date' => $demo->preferred_demo_date->format('Y-m-d'),
            ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame(
            DemoRequestStatus::Approved,
            $demo->refresh()->status
        );
    }

    public function test_the_rejection_workflow_is_available_from_pending(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest();

        $this->actingAs($admin)
            ->patch(route('platform.demo-requests.status', $demo), [
                'status' => 'rejected',
                'internal_notes' => 'Hors cible.',
            ])
            ->assertSessionHasNoErrors();

        $demo->refresh();

        $this->assertSame(DemoRequestStatus::Rejected, $demo->status);
        $this->assertNotNull($demo->rejected_at);
        $this->assertStringContainsString('Hors cible.', $demo->internal_notes);
    }

    public function test_scheduling_updates_the_confirmed_slot(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest();

        $this->actingAs($admin)
            ->patch(route('platform.demo-requests.status', $demo), [
                'status' => 'contacted',
                'preferred_demo_date' => $demo->preferred_demo_date->format('Y-m-d'),
            ]);

        $confirmed = now()->addDays(10)->format('Y-m-d');

        $this->actingAs($admin)
            ->patch(route('platform.demo-requests.status', $demo->refresh()), [
                'status' => 'scheduled',
                'preferred_demo_date' => $confirmed,
                'preferred_demo_time' => '10:00',
            ])
            ->assertSessionHasNoErrors();

        $demo->refresh();

        $this->assertSame($confirmed, $demo->preferred_demo_date->format('Y-m-d'));
        $this->assertNotNull($demo->scheduled_at);
    }

    public function test_internal_notes_can_be_saved_separately(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest();

        $this->actingAs($admin)
            ->patch(route('platform.demo-requests.notes', $demo), [
                'internal_notes' => 'Dirigeant très intéressé.',
            ])
            ->assertSessionHas('success');

        $this->assertSame(
            'Dirigeant très intéressé.',
            $demo->refresh()->internal_notes
        );
    }

    public function test_every_transition_leaves_an_audit_trace(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest();

        $this->actingAs($admin)
            ->patch(route('platform.demo-requests.status', $demo), [
                'status' => 'contacted',
                'preferred_demo_date' => $demo->preferred_demo_date->format('Y-m-d'),
            ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'demo_request.contacted',
            'auditable_type' => DemoRequest::class,
            'auditable_id' => $demo->id,
        ]);
    }

    public function test_approval_creates_no_school_and_no_license_yet(): void
    {
        $admin = $this->platformAdmin();
        $demo = $this->demoRequest();

        foreach (['contacted', 'scheduled', 'demo_done', 'approved'] as $step) {
            $this->actingAs($admin)->patch(
                route('platform.demo-requests.status', $demo),
                [
                    'status' => $step,
                    'preferred_demo_date' => $demo->preferred_demo_date->format('Y-m-d'),
                ]
            );
        }

        // approved ≠ active : l'activation n'a pas encore eu lieu.
        $this->assertSame(
            DemoRequestStatus::Approved,
            $demo->refresh()->status
        );
        $this->assertSame(0, School::withoutGlobalScopes()->count());
        $this->assertSame(0, User::withoutGlobalScopes()->whereNotNull('school_id')->count());
    }
}
