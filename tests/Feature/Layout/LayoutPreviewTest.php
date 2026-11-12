<?php

namespace Tests\Feature\Layout;

use Tests\TestCase;

class LayoutPreviewTest extends TestCase
{
    public function test_preview_is_available_in_testing_with_components_and_manifest(): void
    {
        $this->get('/layout-preview')->assertOk()
            ->assertSee('/manifest.webmanifest')
            ->assertSee('TTS-2611-014')
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('type="tel"', false);
    }

    public function test_preview_is_not_available_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->get('/layout-preview')->assertNotFound();
    }
}
