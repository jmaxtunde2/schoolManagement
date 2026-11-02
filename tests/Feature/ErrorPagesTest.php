<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ErrorPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_page_renders_the_custom_404_page(): void
    {
        $this->get('/page-introuvable-'.uniqid())
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Error')
                ->where('status', 404)
                ->where('title', 'Page introuvable'));
    }

    public function test_forbidden_access_renders_the_custom_403_page(): void
    {
        $user = User::factory()->teacher()->create();

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Error')
                ->where('status', 403)
                ->where('title', 'Accès refusé'));
    }

    public function test_maintenance_page_renders_the_custom_503_page(): void
    {
        Route::middleware('web')->get('/__test/503', fn () => abort(503));

        $this->get('/__test/503')
            ->assertStatus(503)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Error')
                ->where('status', 503)
                ->where('title', 'Service en maintenance'));
    }

    public function test_blade_fallback_uses_the_school_branding(): void
    {
        $html = view('errors.minimal', [
            'status' => 404,
            ...ErrorPage::meta(404),
            'branding' => [
                'id' => 12,
                'short_name' => 'École Horizon',
                'primary_color' => '#116B50',
                'secondary_color' => '#2457A6',
            ],
        ])->render();

        $this->assertStringContainsString('Page introuvable', $html);
        $this->assertStringContainsString('École Horizon', $html);
        $this->assertStringContainsString('#116B50', $html);
    }

    public function test_unexpected_errors_render_the_custom_500_page_outside_debug(): void
    {
        config(['app.debug' => false]);

        Route::middleware('web')->get('/__test/500', function () {
            throw new RuntimeException('Erreur de test.');
        });

        $this->get('/__test/500')
            ->assertStatus(500)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Errors/Error')
                ->where('status', 500)
                ->where('title', 'Erreur interne'));
    }
}
