<?php

namespace Tests\Feature\Tracking;

use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Tts\Detail;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CancelledTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $admin): ServiceOrder
    {
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));

        return app(CreateServiceOrder::class)->create([
            'customer_name' => 'Éka & keluarga', 'customer_phone' => '081234567890', 'customer_address' => 'Alamat pribadi QA',
            'category_id' => Category::firstOrCreate(['name' => 'HP'])->id, 'item_description' => 'Model α', 'complaint' => 'Tidak menyala',
        ], $admin)->refresh();
    }

    public function test_real_cancellation_updates_button_whatsapp_and_escaped_public_reason(): void
    {
        config(['app.url' => 'https://repair.example']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $order = $this->order($admin);
        $technician = User::factory()->create(['role' => 'teknisi']);
        app(ManageServiceOrder::class)->assign($order->id, $technician->id, 'pengecekan');
        $order->update(['inspection_result' => 'Catatan pengecekan privat', 'action_plan' => 'Rencana lama privat']);
        $reason = "Komponen α & baterai tidak tersedia.\n<script>alert('qa')</script>";
        Livewire::test(Detail::class, ['id' => $order->id])->assertSee('Alasan ini akan tampil di link TTS pelanggan')
            ->set('cancel_reason', $reason)->call('cancel')->assertHasNoErrors()->assertRedirect(route('tts.show', $order->id));
        $this->assertSame('batal', $order->fresh()->status->value);
        $this->assertSame($reason, $order->fresh()->cancel_reason);
        $this->get(route('tts.show', $order->id))->assertOk()->assertSee('WA pembatalan')->assertDontSee('WA tanda terima')->assertDontSee('WA hasil pengecekan');

        foreach ([[], ['inspection' => 1]] as $query) {
            $response = $this->get(route('tts.whatsapp', ['id' => $order->id, ...$query]))->assertRedirect();
            $url = $response->headers->get('Location');
            $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);
            parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
            $this->assertCount(1, $parameters);
            $message = $parameters['text'];
            $this->assertStringContainsString('Halo Éka & keluarga, servis '.$order->number.' dibatalkan.', $message);
            $this->assertStringContainsString("Alasan pembatalan: {$reason}", $message);
            $this->assertStringContainsString('https://repair.example/t/'.$order->tracking_token, $message);
            foreach (['Mohon konfirmasi', 'sudah kami terima', 'sudah selesai', 'Catatan pengecekan privat', 'Rencana lama privat'] as $wrongMessage) {
                $this->assertStringNotContainsString($wrongMessage, $message);
            }
        }
        $this->app['auth']->guard()->logout();
        $this->get(route('tracking.show', $order->tracking_token))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Robots-Tag', 'noindex')
            ->assertSee('Servis dibatalkan')->assertSee('Alasan pembatalan')->assertSee($reason)
            ->assertDontSee("<script>alert('qa')</script>", false)->assertDontSee('Alamat pribadi QA')->assertDontSee('6281234567890')
            ->assertDontSee('Catatan pengecekan privat')->assertDontSee('Rencana lama privat');
    }

    public function test_legacy_cancelled_orders_without_reason_use_generic_cancellation_even_with_old_inspection_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        foreach ([null, '', " \n "] as $reason) {
            $order = $this->order($admin);
            $order->update(['status' => 'batal', 'cancel_reason' => $reason, 'cancelled_at' => now()]);
            $response = $this->get(route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]))->assertRedirect();
            parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $parameters);
            $this->assertStringContainsString('servis '.$order->number.' dibatalkan.', $parameters['text']);
            $this->assertStringNotContainsString('Alasan pembatalan:', $parameters['text']);
            $this->assertStringNotContainsString('Mohon konfirmasi', $parameters['text']);
            $this->get(route('tracking.show', $order->tracking_token))->assertOk()->assertSee('Servis dibatalkan')->assertDontSee('Alasan pembatalan');
        }
    }

    public function test_other_statuses_hide_reason_and_cancelled_whatsapp_keeps_admin_and_tenant_guards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order($admin);
        $order->update(['cancel_reason' => 'Alasan lama yang tidak boleh tampil']);
        $this->get(route('tracking.show', $order->tracking_token))->assertOk()->assertDontSee('Alasan pembatalan')->assertDontSee('Alasan lama yang tidak boleh tampil');
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        $order->update(['status' => 'batal', 'cancel_reason' => 'Dibatalkan pelanggan', 'cancelled_at' => now()]);
        $this->get(route('tts.whatsapp', $order->id))->assertRedirect('/login');
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        $technician = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($technician)->get(route('tts.whatsapp', $order->id))->assertForbidden();
        $this->actingAs($admin)->get(route('tts.whatsapp', $order->id))->assertRedirect();
        $other = Tenant::create(['name' => 'Toko asing QA']);
        $otherAdmin = User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']);
        $foreign = $this->order($otherAdmin);
        $foreign->update(['status' => 'batal', 'cancel_reason' => 'Rahasia toko asing', 'cancelled_at' => now()]);
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        $this->actingAs($admin)->get(route('tts.whatsapp', ['id' => $foreign->id, 'inspection' => 1]))->assertNotFound();
        $this->get(route('tts.show', $foreign->id))->assertNotFound();
        $this->get(route('tracking.show', $order->tracking_token))->assertOk()->assertSee('Dibatalkan pelanggan')->assertDontSee('Rahasia toko asing');
    }
}
