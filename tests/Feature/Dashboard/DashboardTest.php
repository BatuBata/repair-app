<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\Index;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);
        $this->travelTo(CarbonImmutable::parse('2026-12-08 09:00:00', 'Asia/Makassar'));
    }

    private function service(array $attributes = [], int $price = 1000000, int $qty = 1, int $cost = 0): ServiceOrder
    {
        $o = ServiceOrder::create(array_merge(['number' => 'TTS-UJI-'.(ServiceOrder::count() + 1), 'customer_name' => 'Ibu Éka & Putra', 'customer_phone' => '081234567890', 'category_id' => Category::firstOrCreate(['name' => 'Laptop'])->id, 'complaint' => 'LCD rusak', 'created_by' => $this->admin->id, 'created_at' => '2026-12-08 08:00:00', 'status' => 'selesai', 'invoiced_on' => '2026-11-11', 'warranty_days' => 30, 'warranty_until' => '2026-12-11'], $attributes));
        $o->items()->create(['type' => 'part', 'description' => 'LCD', 'qty' => $qty, 'price' => $price, 'cost' => $cost]);

        return $o;
    }

    private function sale(array $attributes = [], int $price = 250000, int $qty = 1, int $cost = 0): Sale
    {
        $o = Sale::create(array_merge(['number' => 'JL-UJI-'.(Sale::count() + 1), 'sold_on' => '2026-11-11', 'customer_name' => 'Pak Joko', 'customer_phone' => '081234567890', 'created_by' => $this->admin->id, 'warranty_days' => 30, 'warranty_until' => '2026-12-11'], $attributes));
        $product = Product::create(['name' => 'Printer Canon', 'cost_price' => $cost, 'sell_price' => $price, 'stock' => 0]);
        $o->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'qty' => $qty, 'price' => $price, 'cost' => $cost]);

        return $o;
    }

    public function test_daily_counts_backlog_and_revenue_follow_real_dates_and_statuses(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-11 14:00:00', 'Asia/Makassar'));
        $received = $this->service(['status' => 'diterima', 'invoiced_on' => null, 'created_at' => '2026-11-11 00:00:00']);
        $this->service(['status' => 'dikerjakan', 'created_at' => '2026-11-11 08:00:00'], 999);
        $ready = $this->service(['invoiced_on' => '2026-11-11', 'created_at' => '2026-11-11 09:00:00'], 1100000);
        $this->service(['status' => 'diambil', 'invoiced_on' => '2026-11-11', 'picked_up_on' => '2026-11-11', 'created_at' => '2026-11-11 09:00:00'], 1250000);
        $this->service(['status' => 'batal', 'invoiced_on' => '2026-11-11', 'created_at' => '2026-11-11 09:00:00'], 999);
        $old = $this->service(['invoiced_on' => '2026-11-10', 'created_at' => '2026-11-10 23:59:59'], 999);
        $this->sale(['sold_on' => '2026-11-11']);
        $this->sale(['sold_on' => '2026-11-10'], 999);
        Livewire::test(Index::class)->assertViewHas('incoming', 5)
            ->assertViewHas('counts', fn ($v) => $v['selesai'] === 2 && $v['batal'] === 1 && $v['diterima'] === 1)
            ->assertViewHas('backlog', fn ($v) => $v->modelKeys() === [$old->id, $ready->id])
            ->assertViewHas('revenue', ['total' => 'Rp2.600.000', 'service' => 'Rp2.350.000', 'sales' => 'Rp250.000'])
            ->assertSeeText('Rp2.600.000');
        $this->get('/')->assertOk()->assertSee('Selesai belum diambil')->assertSee($ready->number)->assertDontSee('Kelola pengguna');
        $this->get('/dashboard')->assertRedirect('/');
        $this->assertNotSame($received->id, $ready->id);
    }

    public function test_aggregate_beyond_php_int_max_and_sqlite_sum_is_exact_without_public_financial_state(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $this->service(['invoiced_on' => '2026-12-08'], 4294967295, 2147483648);
            $this->sale(['sold_on' => '2026-12-08'], 4294967295, 2147483648);
        }
        $c = Livewire::test(Index::class)->assertViewHas('revenue', [
            'total' => 'Rp36.893.488.138.829.168.640', 'service' => 'Rp18.446.744.069.414.584.320', 'sales' => 'Rp18.446.744.069.414.584.320',
        ]);
        $this->assertArrayNotHasKey('revenue', $c->snapshot['data']);
        $this->assertArrayNotHasKey('reminders', $c->snapshot['data']);
    }

    public function test_warranty_window_is_inclusive_wita_for_services_and_sales(): void
    {
        $this->service(['status' => 'diambil', 'picked_up_on' => '2026-11-12']);
        $this->sale();
        foreach ([['2026-12-07 23:59:59', 0], ['2026-12-08 00:00:00', 2], ['2026-12-11 23:59:59', 2], ['2026-12-12 00:00:00', 0]] as [$time, $count]) {
            $this->travelTo(CarbonImmutable::parse($time, 'Asia/Makassar'));
            Livewire::test(Index::class)->assertViewHas('reminders', fn ($v) => $v->count() === $count);
        }
    }

    public function test_zero_contacted_expired_cancelled_and_unissued_warranties_are_excluded(): void
    {
        $this->service(['warranty_days' => 0]);
        $this->service(['warranty_contacted_at' => now()]);
        $this->service(['warranty_until' => '2026-12-07']);
        $this->service(['status' => 'batal']);
        $this->service(['status' => 'dikerjakan', 'invoiced_on' => null]);
        $this->service(['warranty_until' => '2026-12-12']);
        $this->sale(['warranty_days' => 0]);
        $this->sale(['warranty_contacted_at' => now()]);
        $this->sale(['warranty_until' => '2026-12-07']);
        $this->sale(['warranty_until' => '2026-12-12']);
        Livewire::test(Index::class)->assertViewHas('reminders', fn ($v) => $v->isEmpty())->assertSee('Tidak ada garansi yang akan habis');
    }

    public function test_manual_whatsapp_links_use_existing_encoded_template_and_historical_names(): void
    {
        $service = $this->service();
        $sale = $this->sale();
        $service->category->update(['is_active' => false]);
        $c = Livewire::test(Index::class)->assertViewHas('reminders', function ($rows) {
            $service = $rows->firstWhere('kind', 'service');
            $sale = $rows->firstWhere('kind', 'sale');
            $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $service['url']);
            $this->assertStringContainsString('Halo Ibu Éka & Putra, garansi', rawurldecode($service['url']));
            $this->assertStringContainsString('Laptop berlaku s.d. 11 Des 2026', rawurldecode($service['url']));
            $this->assertStringContainsString('Printer Canon', rawurldecode($sale['url']));

            return true;
        });
        $this->assertNull($service->fresh()->warranty_contacted_at);
        $this->assertNull($sale->fresh()->warranty_contacted_at);
        $c->call('markContacted', 'service', $service->id)->assertHasNoErrors()->assertViewHas('reminders', fn ($v) => $v->count() === 1);
        $stamp = $service->fresh()->warranty_contacted_at;
        $c->call('markContacted', 'service', $service->id)->assertHasErrors('reminder');
        $this->assertEquals($stamp, $service->fresh()->warranty_contacted_at);
        $c->call('markContacted', 'sale', $sale->id)->assertHasNoErrors()->assertViewHas('reminders', fn ($v) => $v->isEmpty());
        $this->assertNotNull($sale->fresh()->warranty_contacted_at);
    }

    public function test_mark_rechecks_eligibility_after_mount_and_rejects_invalid_ids_and_kinds(): void
    {
        $o = $this->service();
        $c = Livewire::test(Index::class);
        foreach ([['App\\Models\\User', $o->id, 'kind'], ['sale', -1, 'id'], ['service', '1 OR 1=1', 'id'], ['service', '1.5', 'id'], ['service', 999999, 'reminder']] as [$kind, $id, $error]) {
            $c->call('markContacted', $kind, $id)->assertHasErrors($error);
        }
        $o->update(['status' => 'batal']);
        $c->call('markContacted', 'service', $o->id)->assertHasErrors('reminder');
        $o->update(['status' => 'selesai', 'warranty_days' => 0]);
        $c->call('markContacted', 'service', $o->id)->assertHasErrors('reminder');
        $o->update(['warranty_days' => 30]);
        $this->travelTo(CarbonImmutable::parse('2026-12-12', 'Asia/Makassar'));
        $c->call('markContacted', 'service', $o->id)->assertHasErrors('reminder');
        $this->assertNull($o->fresh()->warranty_contacted_at);
    }

    public function test_technician_and_revoked_admin_are_stopped_before_financial_queries(): void
    {
        $o = $this->service(['invoiced_on' => '2026-12-08'], 123456789);
        $c = Livewire::test(Index::class);
        User::whereKey($this->admin->id)->update(['role' => 'teknisi']);
        DB::enableQueryLog();
        $c->call('markContacted', 'service', $o->id)->assertForbidden();
        $this->get('/')->assertForbidden()->assertDontSee('123.456.789');
        Livewire::test(Index::class)->assertForbidden();
        $queries = implode(' ', array_column(DB::getQueryLog(), 'query'));
        $this->assertStringNotContainsString('service_order_items', $queries);
        $this->assertStringNotContainsString('sale_items', $queries);
        $this->assertNull($o->fresh()->warranty_contacted_at);
    }

    public function test_inactive_admin_and_midnight_change_cannot_mark_stale_sale(): void
    {
        $sale = $this->sale();
        $c = Livewire::test(Index::class);
        $this->travelTo(CarbonImmutable::parse('2026-12-12', 'Asia/Makassar'));
        $c->call('markContacted', 'sale', $sale->id)->assertHasErrors('reminder');
        $this->assertNull($sale->fresh()->warranty_contacted_at);
        User::whereKey($this->admin->id)->update(['is_active' => false]);
        $c->call('markContacted', 'sale', $sale->id)->assertForbidden();
        $this->assertNull($sale->fresh()->warranty_contacted_at);
    }

    public function test_wita_daily_count_uses_storage_timezone_and_exclusive_upper_boundary(): void
    {
        config(['app.timezone' => 'UTC']);
        $this->travelTo(CarbonImmutable::parse('2026-12-08 00:05:00', 'Asia/Makassar'));
        foreach (['2026-12-07 15:59:59', '2026-12-07 16:00:00', '2026-12-08 15:59:59', '2026-12-08 16:00:00'] as $time) {
            $this->service(['created_at' => $time]);
        }
        Livewire::test(Index::class)->assertViewHas('incoming', 2);
    }

    public function test_monthly_chart_rolls_up_real_dates_counts_notes_once_and_keeps_daily_metrics_separate(): void
    {
        $first = $this->service(['invoiced_on' => '2026-12-01'], 1000000, 1, 700000);
        $first->items()->create(['type' => 'jasa', 'description' => 'Jasa ganti', 'qty' => 1, 'price' => 100000, 'cost' => 0]);
        $this->service(['invoiced_on' => '2026-12-02', 'unrepairable' => true], 0);
        $this->service(['invoiced_on' => '2026-12-03', 'status' => 'diambil', 'picked_up_on' => '2026-12-04'], 500000, 1, 600000);
        $this->service(['invoiced_on' => '2026-12-03', 'status' => 'batal'], 999);
        $this->service(['invoiced_on' => '2026-11-30'], 777);
        $this->service(['invoiced_on' => '2026-12-09'], 333);
        $this->sale(['sold_on' => '2026-12-04'], 250000, 1, 100000);
        $this->sale(['sold_on' => '2026-11-30'], 999);
        $c = Livewire::test(Index::class)->assertViewHas('revenue', ['total' => 'Rp0', 'service' => 'Rp0', 'sales' => 'Rp0'])
            ->assertViewHas('chart', function ($v) {
                $this->assertSame(['revenue' => 'Rp1.850.000', 'profit' => 'Rp450.000', 'invoices' => '3'], $v['totals']);
                $this->assertCount(8, $v['points']);
                $this->assertSame(6, $v['scale_exponent']);
                $this->assertSame(['0', '0,5', '1', '1,5'], array_column($v['ticks'], 'label'));
                $this->assertSame('1100000', $v['points'][0]['raw']['revenue']);
                $this->assertSame('400000', $v['points'][0]['raw']['profit']);
                $this->assertSame('1', $v['points'][0]['raw']['invoices']);
                $this->assertSame('1', $v['points'][1]['raw']['invoices']);
                $this->assertSame('0', $v['points'][1]['raw']['revenue']);
                $this->assertSame('-100000', $v['points'][2]['raw']['profit']);
                $this->assertSame('0', $v['points'][3]['raw']['invoices']);
                $this->assertSame('0', $v['points'][7]['raw']['revenue']);

                return true;
            });
        $c->call('selectSeries', 'profit')->assertSet('series', 'profit')->assertViewHas('chart', function ($v) {
            $this->assertSame('-100000', $v['min']);
            $this->assertSame('400000', $v['max']);
            $this->assertSame('Rp-100.000', $v['points'][2]['value']);
            $this->assertTrue(collect($v['ticks'])->contains(fn ($tick) => str_starts_with($tick['raw'], '-')));

            return true;
        });
        $c->call('selectSeries', 'invoices')->assertSet('series', 'invoices')->assertViewHas('chart', fn ($v) => $v['points'][0]['value'] === '1' && $v['totals']['invoices'] === '3');
    }

    public function test_monthly_chart_handles_all_zero_and_single_day_without_division_or_false_income(): void
    {
        foreach (['2026-12-01 00:05:00', '2026-12-08 09:00:00'] as $time) {
            $this->travelTo(CarbonImmutable::parse($time, 'Asia/Makassar'));
            $c = Livewire::test(Index::class);
            foreach (['revenue', 'profit', 'invoices'] as $series) {
                $c->call('selectSeries', $series)->assertViewHas('chart', function ($v) {
                    $this->assertTrue($v['empty']);
                    $this->assertSame('0', $v['min']);
                    $this->assertSame('0', $v['max']);
                    $this->assertCount(1, $v['ticks']);
                    $this->assertSame('0', $v['ticks'][0]['raw']);
                    $this->assertSame(220.0, $v['ticks'][0]['y']);
                    $this->assertStringNotContainsString('NAN', $v['polyline']);
                    $this->assertStringNotContainsString('INF', $v['polyline']);

                    return true;
                });
            }
        }
    }

    public function test_monthly_chart_large_negative_profit_and_tooltip_values_stay_exact(): void
    {
        $this->service(['invoiced_on' => '2026-12-08'], 0, 2147483648, 4294967295);
        $this->sale(['sold_on' => '2026-12-08'], 0, 2147483648, 4294967295);
        $c = Livewire::test(Index::class)->call('selectSeries', 'profit')->assertViewHas('chart', function ($v) {
            $this->assertSame('Rp0', $v['totals']['revenue']);
            $this->assertSame('Rp-18.446.744.069.414.584.320', $v['totals']['profit']);
            $this->assertSame('-18446744069414584320', $v['min']);
            $this->assertSame(18, $v['scale_exponent']);
            $this->assertSame(['-20', '-15', '-10', '-5', '0'], array_column($v['ticks'], 'label'));
            $this->assertSame('0', $v['max']);
            $this->assertSame('Rp-18.446.744.069.414.584.320', $v['points'][7]['value']);
            $this->assertSame('Rp-9.223.372.034.707.292.160', $v['points'][7]['service']);
            $this->assertSame('Rp-9.223.372.034.707.292.160', $v['points'][7]['sales']);
            $this->assertGreaterThan(0, $v['points'][7]['y']);
            $this->assertLessThanOrEqual(220, $v['points'][7]['y']);
            $this->assertFalse($v['empty']);

            return true;
        });
        $this->assertArrayNotHasKey('chart', $c->snapshot['data']);
        $this->assertSame('profit', $c->snapshot['data']['series']);
    }

    public function test_chart_series_is_validated_and_fresh_admin_is_required_for_switching(): void
    {
        $c = Livewire::test(Index::class)->call('selectSeries', 'cost')->assertHasErrors('series');
        $c->call('selectSeries', 'invoices')->assertHasNoErrors()->assertSet('series', 'invoices');
        User::whereKey($this->admin->id)->update(['role' => 'teknisi']);
        $c->call('selectSeries', 'profit')->assertForbidden();
    }

    public function test_contact_rechecks_wita_after_locked_read_crosses_midnight(): void
    {
        foreach (['service' => $this->service(), 'sale' => $this->sale()] as $kind => $record) {
            $this->travelTo(CarbonImmutable::parse('2026-12-11 23:59:59', 'Asia/Makassar'));
            $component = Livewire::test(Index::class);
            $class = $record::class;
            $class::retrieved(function ($locked) use ($record) {
                if ($locked->id === $record->id) {
                    $this->travelTo(CarbonImmutable::parse('2026-12-12 00:00:00', 'Asia/Makassar'));
                }
            });
            try {
                $component->call('markContacted', $kind, $record->id)->assertHasErrors('reminder');
            } finally {
                $class::getEventDispatcher()->forget('eloquent.retrieved: '.$class);
            }
            $this->assertNull($record->fresh()->warranty_contacted_at);
        }
    }
}
