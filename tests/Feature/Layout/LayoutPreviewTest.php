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

    public function test_role_and_navigation_match_paths_without_starter_links(): void
    {
        $admin = $this->get('/layout-preview?page=servis')->assertOk();
        $admin->assertSee('Pak Arif')->assertSee('Admin')->assertDontSee('Repository')->assertDontSee('Documentation');
        $dom = new \DOMDocument;
        @$dom->loadHTML($admin->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//a[@href="/tts" and @aria-current="page"]')->length);

        $technician = $this->get('/layout-preview?role=teknisi&page=buat')->assertOk();
        $technician->assertSee('Rizky')->assertSee('Teknisi')->assertDontSee('Penjualan');
        @$dom->loadHTML($technician->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//a[@href="/tts/baru" and @aria-current="page"]')->length);
        $this->assertSame(0, $xpath->query('//a[@href="/tts" and @aria-current="page"]')->length);

        $menu = $this->get('/layout-preview?page=menu')->assertOk();
        @$dom->loadHTML($menu->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//button[@class="bottom-menu" and @aria-current="page"]')->length);

        $detail = $this->get('/layout-preview?role=teknisi&page=servis')->assertOk();
        @$dom->loadHTML($detail->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//a[@href="/tts" and @aria-current="page"]')->length);
    }
}
