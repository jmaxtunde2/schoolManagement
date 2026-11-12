<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Pages d'information publiques de la plateforme CoriSchool.
 */
class PublicInfoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_information_pages_are_available(): void
    {
        $pages = [
            '/contact' => 'Public/Contact',
            '/conditions-utilisation' => 'Public/Terms',
            '/confidentialite' => 'Public/Privacy',
        ];

        foreach ($pages as $uri => $component) {
            $this->get($uri)
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->has('support.whatsapp_message')
                    ->has('support.email')
                );
        }
    }
}
