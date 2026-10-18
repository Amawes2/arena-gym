<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicLandingPageTest extends TestCase
{
    public function test_public_landing_shell_has_production_metadata_and_arena_branding(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>ARENA GYM — Gestion de salles de sport</title>', false)
            ->assertSee('<meta name="description"', false)
            ->assertSee('<meta property="og:title"', false)
            ->assertSee('href="/images/arena-icon-512.png"', false)
            ->assertSee('id="elitefit-root"', false);
    }

    public function test_unknown_public_page_paths_still_load_the_single_page_application(): void
    {
        $this->get('/fonctionnalite-inconnue')
            ->assertOk()
            ->assertSee('id="elitefit-root"', false);
    }
}
