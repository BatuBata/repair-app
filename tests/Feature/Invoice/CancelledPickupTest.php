<?php

namespace Tests\Feature\Invoice;

use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Tts\Detail;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Tests\TestCase;

class CancelledPickupTest extends TestCase
{
    use RefreshDatabase;

    private function cancelled(User $admin): ServiceOrder
    {
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        $this->actingAs($admin);
        $order = app(CreateServiceOrder::class)->create([
            'customer_name' => 'Pelanggan pengambilan QA', 'customer_phone' => '081234567890', 'customer_address' => 'Alamat privat QA',
            'category_id' => Category::firstOrCreate(['name' => 'HP'])->id, 'complaint' => 'Mati total',
        ], $admin);
        app(ManageServiceOrder::class)->cancel($order->id, 'Suku cadang tidak tersedia');

        return $order->refresh();
    }

    public function test_cancelled_pickup_uses_server_wita_today_and_preserves_status_and_finances(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-19 15:55:00', 'UTC'));
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->cancelled($admin);
        $before = $order->getRawOriginal();
        $this->get(route('tts.show', $order->id))->assertOk()->assertSee('Barang belum diambil')->assertSee('Tandai barang diambil');
        $this->get(route('tracking.show', $order->tracking_token))->assertOk()->assertSee('Barang belum diambil')->assertDontSee('Alamat privat QA');
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        // UTC is still 19 Nov while WITA has already rolled over to 20 Nov.
        $this->travelTo(CarbonImmutable::parse('2026-11-19 16:05:00', 'UTC'));
        Livewire::test(Detail::class, ['id' => $order->id])->set('picked_up_on', '1990-01-01')->set('payment_method', 'transfer')->set('payment_bank', 'invalid-bank')->call('collect')
            ->assertHasNoErrors()->assertRedirect(route('tts.show', $order->id));
        $after = $order->fresh();
        $this->assertSame('2026-11-20', $after->picked_up_on->toDateString());
        $this->assertSame('batal', $after->status->value);
        $this->assertSame(Arr::except($before, ['picked_up_on', 'updated_at']), Arr::except($after->getRawOriginal(), ['picked_up_on', 'updated_at']));
        $this->assertNull($after->invoiced_on);
        $this->assertNull($after->paid_at);
        $this->assertNull($after->payment_method);
        $this->assertNull($after->payment_bank);
        $this->assertSame(0, $after->items()->count());
        $this->get(route('tts.show', $order->id))->assertOk()->assertSee('Barang sudah diambil')->assertSee('20 Nov 2026')->assertSee('Suku cadang tidak tersedia')->assertDontSee('Tandai barang diambil');
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        $technician = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($technician)->get(route('tts.show', $order->id))->assertOk()->assertSee('Barang sudah diambil')->assertSee('20 Nov 2026')->assertDontSee('Tandai barang diambil');
        $this->app['auth']->guard()->logout();
        $this->get(route('tracking.show', $order->tracking_token))->assertOk()->assertSee('Servis dibatalkan')->assertSee('Barang sudah diambil')->assertSee('20 Nov 2026')->assertSee('Suku cadang tidak tersedia')
            ->assertDontSee('Alamat privat QA')->assertDontSee('6281234567890');
    }

    public function test_duplicate_or_stale_pickup_does_not_overwrite_first_date_or_updated_time(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00:00', 'Asia/Makassar'));
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->cancelled($admin);
        $stale = Livewire::test(Detail::class, ['id' => $order->id]);
        app(ManageServiceOrder::class)->collect($order->id, 'not-a-date', ['payment_method' => 'invalid']);
        $first = $order->fresh()->getRawOriginal();
        $this->travel(1)->days();
        $stale->set('picked_up_on', '2099-12-31')->call('collect')->assertHasNoErrors();
        $this->assertSame($first, $order->fresh()->getRawOriginal());
        $this->assertSame('2026-11-20', $order->fresh()->picked_up_on->toDateString());
    }

    public function test_technicians_are_read_only_and_cross_tenant_or_inactive_users_cannot_collect(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->cancelled($admin);
        $technician = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($technician);
        Livewire::test(Detail::class, ['id' => $order->id])->assertSee('Barang belum diambil')->assertDontSee('Tandai barang diambil')->call('collect')->assertForbidden();
        $this->assertNull($order->fresh()->picked_up_on);
        $other = Tenant::create(['name' => 'Toko asing QA']);
        $otherAdmin = User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']);
        $foreign = $this->cancelled($otherAdmin);
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        $this->actingAs($admin);
        Livewire::test(Detail::class, ['id' => $foreign->id])->assertNotFound();
        $this->assertDatabaseHas('service_orders', ['id' => $foreign->id, 'picked_up_on' => null, 'status' => 'batal']);
        $component = Livewire::test(Detail::class, ['id' => $order->id]);
        $admin->update(['is_active' => false]);
        $component->call('collect')->assertForbidden();
        $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'picked_up_on' => null, 'status' => 'batal']);
    }

    public function test_cancelled_pickup_stays_out_of_revenue_and_collected_success_dashboard_count(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00:00', 'Asia/Makassar'));
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->cancelled($admin);
        app(ManageServiceOrder::class)->collect($order->id, '2026-11-20');
        $report = app(Report::class)->build('2026-11-01', '2026-11-30');
        $this->assertSame([], $report['rows']);
        $this->assertSame('0', $report['revenue']);
        $this->assertSame('0', $report['net']);
        Livewire::test(Dashboard::class)->assertViewHas('counts', fn ($counts) => $counts->get('batal') === 1 && $counts->get('diambil', 0) === 0)
            ->assertViewHas('lane', fn ($lane) => $lane['diambil']['count'] === 0)
            ->assertViewHas('backlog', fn ($backlog) => $backlog->isEmpty());
    }
}
