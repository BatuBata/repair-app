<?php

namespace Tests\Feature\Tracking;

use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private function order(): ServiceOrder
    {
        return app(CreateServiceOrder::class)->create([
            'customer_name' => 'Ibu Éka & Putra', 'customer_phone' => '081234567890', 'customer_address' => 'Alamat Rahasia',
            'category_id' => Category::firstOrCreate(['name' => 'Laptop'])->id, 'item_description' => 'Model α', 'complaint' => 'Layar rusak',
        ], User::factory()->create(['role' => 'teknisi']));
    }

    public function test_public_tracking_is_exact_and_discloses_only_public_information(): void
    {
        $order = $this->order();
        $order->forceFill(['tracking_token' => str_repeat('aB', 32)])->save();
        $this->get('/t/'.str_repeat('Ab', 32))->assertNotFound()->assertHeader('X-Robots-Tag', 'noindex');
        $this->get('/t/short')->assertNotFound();
        $this->get('/t/'.$order->tracking_token)->assertOk()->assertHeader('X-Robots-Tag', 'noindex')
            ->assertHeader('Cache-Control', 'no-store, private')->assertSee('Diterima')->assertSee('Layar rusak')->assertDontSee('081234567890')->assertDontSee('6281234567890')->assertDontSee('Alamat Rahasia')->assertDontSee('Total');
        $order->update(['status' => 'dikerjakan', 'assigned_at' => now()]);
        $this->get('/t/'.$order->tracking_token)->assertSee('sedang')->assertSee('dikerjakan');
        $order->update(['status' => 'selesai', 'invoiced_on' => '2026-11-10', 'warranty_days' => 30, 'warranty_until' => '2026-12-10']);
        $order->items()->create(['type' => 'part', 'description' => 'LCD', 'qty' => 1, 'price' => 1000000, 'cost' => 700000]);
        $order->items()->create(['type' => 'jasa', 'description' => 'Jasa', 'qty' => 1, 'price' => 100000, 'cost' => 0]);
        $this->get('/t/'.$order->tracking_token)->assertOk()->assertSee('Rp1.100.000')->assertSee('Garansi 30 hari')->assertDontSee('700.000')->assertDontSee('Alamat Rahasia')->assertDontSee('081234567890')->assertDontSee('6281234567890')->assertDontSee('wire:snapshot');
        $order->update(['status' => 'diambil', 'picked_up_on' => '2026-11-11', 'unrepairable' => true, 'warranty_days' => 0, 'warranty_until' => null]);
        $this->get('/t/'.$order->tracking_token)->assertSee('sudah diambil')->assertSee('tidak dapat diperbaiki')->assertSee('Tidak ada garansi');
        $order->items()->update(['price' => 0]);
        $this->get('/t/'.$order->tracking_token)->assertSee('Rp0');
        $order->update(['status' => 'batal', 'invoiced_on' => null, 'cancelled_at' => now()]);
        $this->get('/t/'.$order->tracking_token)->assertSee('dibatalkan')->assertDontSee('Rp1.100.000');
    }

    public function test_whatsapp_is_admin_only_and_encodes_receipt_and_invoice(): void
    {
        $order = $this->order();
        config(['app.url' => 'https://servis.example']);
        $admin = User::factory()->create(['role' => 'admin']);
        $tech = User::factory()->create(['role' => 'teknisi']);
        $this->get(route('tts.whatsapp', $order->id))->assertRedirect('/login');
        $this->actingAs($tech)->get(route('tts.whatsapp', $order->id))->assertForbidden();
        $this->actingAs($tech)->get(route('tts.show', $order->id))->assertDontSee('Kirim WA');
        $url = $this->actingAs($admin)->get(route('tts.whatsapp', $order->id))->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);
        $message = rawurldecode(parse_url($url, PHP_URL_QUERY));
        $this->assertStringContainsString('Ibu Éka & Putra', $message);
        $this->assertStringContainsString('https://servis.example/t/'.$order->tracking_token, $message);
        $order->update(['status' => 'selesai', 'invoiced_on' => '2026-11-10', 'warranty_until' => '2026-12-10']);
        $order->items()->create(['type' => 'part', 'description' => 'LCD', 'qty' => 1, 'price' => 1000000, 'cost' => 700000]);
        $url = $this->get(route('tts.whatsapp', $order->id))->headers->get('Location');
        $this->assertStringContainsString('Total Rp1.000.000', rawurldecode($url));
        $order->update(['warranty_days' => 0, 'warranty_until' => '2026-11-10']);
        $url = $this->get(route('tts.whatsapp', $order->id))->headers->get('Location');
        $this->assertStringContainsString('Tanpa garansi', rawurldecode($url));
        $this->assertStringNotContainsString('Garansi s.d.', rawurldecode($url));
        $this->assertSame('Halo Éka, garansi TTS-1 untuk Laptop berlaku s.d. 10 Des 2026. Apakah barangnya masih baik? — Anugerah', WhatsApp::warrantyMessage('Éka', 'TTS-1', 'Laptop', '10 Des 2026', 'Anugerah'));
        $admin->update(['role' => 'teknisi']);
        $this->get(route('tts.whatsapp', $order->id))->assertForbidden();
        $admin->update(['is_active' => false]);
        $this->get(route('tts.whatsapp', $order->id))->assertForbidden();
    }
}
