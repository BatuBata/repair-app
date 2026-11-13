<?php

namespace Tests\Feature\Reports;

use App\Actions\Sales\DeleteSale;
use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Reports\Index;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StockEntry;
use App\Models\User;
use App\Services\Reports\Report;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);
        $this->travelTo(CarbonImmutable::parse('2026-11-13 09:00:00', 'Asia/Makassar'));
    }

    private function order(string $date = '2026-11-11', string $status = 'selesai'): ServiceOrder
    {
        static $number = 0;
        $order = ServiceOrder::create(['number' => 'TTS-TEST-'.++$number, 'customer_name' => 'Bu Ratna', 'customer_phone' => '081234567890', 'category_id' => Category::firstOrCreate(['name' => 'Laptop'])->id, 'complaint' => 'LCD bergaris', 'item_description' => 'Laptop', 'status' => $status, 'invoiced_on' => $date, 'created_by' => $this->admin->id]);
        $order->items()->createMany([['type' => 'part', 'description' => 'LCD', 'qty' => 1, 'price' => 1000000, 'cost' => 700000], ['type' => 'jasa', 'description' => 'Ganti LCD', 'qty' => 1, 'price' => 100000, 'cost' => 0]]);

        return $order;
    }

    private function sale(string $date = '2026-11-11', int $qty = 1, int $price = 1500000, int $cost = 1100000): Sale
    {
        static $number = 0;
        $product = Product::create(['name' => 'Printer second', 'cost_price' => $cost, 'sell_price' => $price, 'stock' => 0]);
        $sale = Sale::create(['number' => 'JL-TEST-'.++$number, 'sold_on' => $date, 'created_by' => $this->admin->id]);
        $sale->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'qty' => $qty, 'price' => $price, 'cost' => $cost]);

        return $sale;
    }

    private function expense(string $date = '2026-11-11', int $amount = 15000, string $category = 'bensin'): void
    {
        Expense::create(['spent_on' => $date, 'amount' => $amount, 'category' => $category, 'created_by' => $this->admin->id]);
    }

    public function test_prd_sample_and_restocks_are_information_only(): void
    {
        $this->order();
        $this->sale();
        $this->expense();
        $this->expense(amount: 50000, category: 'konsumsi');
        $this->order(status: 'batal');
        $this->order(status: 'dikerjakan');
        $unissued = $this->order();
        $unissued->update(['invoiced_on' => null]);
        $product = Product::firstOrFail();
        StockEntry::create(['product_id' => $product->id, 'qty' => 2, 'cost_price' => 1000000, 'entry_date' => '2026-11-11', 'created_by' => $this->admin->id]);
        $report = app(Report::class)->build('2026-11-11', '2026-11-11');
        foreach (['revenue' => '2600000', 'cost' => '1800000', 'gross' => '800000', 'expenses' => '65000', 'net' => '735000', 'restock' => '2000000'] as $key => $value) {
            $this->assertSame($value, $report[$key], $key);
        }
        $this->assertCount(4, $report['rows']);
        $this->assertSame('2600000', $report['days'][0]['amount']);
        $this->assertSame('1100000', $report['days'][0]['servis']);
        $this->assertSame('1500000', $report['days'][0]['jual']);
        $this->get('/laporan')->assertOk()->assertSee('Laba rugi')->assertSee('Omzet per hari');
        Livewire::test(Index::class)->set('from', '2026-11-11')->set('to', '2026-11-11')->assertSeeText('Rp735.000')->assertSee('Restok (informasi)')->set('type', 'servis')->assertViewHas('rows', fn ($rows) => count($rows) === 1)->assertViewHas('totals', fn ($totals) => $totals['amount'] === '1100000')->set('type', 'keluar')->assertViewHas('totals', fn ($totals) => $totals['profit'] === '-65000')->set('detail', $report['rows'][0]['key'])->assertSee('report-detail-title', false);
    }

    public function test_inclusive_dates_default_wita_and_quick_choices(): void
    {
        $this->sale('2026-11-10');
        $this->sale('2026-11-11');
        $this->sale('2026-11-12');
        $this->expense('2026-11-11', 1);
        $report = app(Report::class)->build('2026-11-10', '2026-11-11');
        $this->assertSame('3000000', $report['revenue']);
        $this->assertSame('1', $report['expenses']);
        $this->travelTo(CarbonImmutable::parse('2026-11-30 16:05:00', 'UTC'));
        Livewire::test(Index::class)->assertSet('from', '2026-12-01')->assertSet('to', '2026-12-01')->call('period', 'week')->assertSet('from', '2026-11-25')->assertSet('to', '2026-12-01')->call('period', 'today')->assertSet('from', '2026-12-01')->call('period', 'month')->assertSet('from', '2026-12-01');
    }

    public function test_invalid_empty_and_legal_date_boundaries(): void
    {
        foreach (['', 'tomorrow', '2026-1-2', '2026-02-30', '0999-12-31', '10000-01-01'] as $from) {
            Livewire::test(Index::class)->set('from', $from)->assertViewHas('report', null)->assertSee('Periksa periode laporan');
        }
        Livewire::test(Index::class)->set('from', '2026-11-14')->set('to', '2026-11-13')->assertViewHas('report', null)->assertSee('Sampai tanggal harus sama atau setelah Dari tanggal.');
        Livewire::test(Index::class)->set('type', 'unknown')->assertViewHas('report', null)->assertSee('Jenis transaksi tidak valid.');
        foreach (['1000-01-01', '9999-12-31'] as $date) {
            $report = app(Report::class)->build($date, $date);
            $this->assertSame('0', $report['net']);
            $this->assertCount(1, $report['days']);
            $this->assertSame('0', $report['days'][0]['amount']);
        }
        $full = app(Report::class)->build('1000-01-01', '9999-12-31', PHP_INT_MAX);
        $this->assertLessThanOrEqual(31, count($full['days']));
        $this->assertSame('9999-12-31', $full['days'][array_key_last($full['days'])]['date']);
        Livewire::test(Index::class)->assertSee('Tidak ada transaksi')->assertSeeText('Rp0');
    }

    public function test_correction_uses_latest_values_with_original_invoice_date_and_sale_deletion(): void
    {
        $order = $this->order();
        $sale = $this->sale();
        $technician = User::factory()->create(['role' => 'teknisi']);
        $order->update(['technician_id' => $technician->id]);
        $this->travelTo(CarbonImmutable::parse('2026-11-15', 'Asia/Makassar'));
        app(ManageServiceOrder::class)->invoice($order->id, ['rows' => [['type' => 'part', 'description' => 'LCD koreksi', 'qty' => 1, 'price' => 900000, 'cost' => 700000]], 'warranty_days' => 30, 'unrepairable' => false], true, InvoiceLines::revision($order->fresh()));
        $this->assertSame('2026-11-11', $order->fresh()->invoiced_on->toDateString());
        $this->assertSame('2400000', app(Report::class)->build('2026-11-11', '2026-11-11')['revenue']);
        app(DeleteSale::class)->handle($sale->id);
        $this->assertSame('900000', app(Report::class)->build('2026-11-11', '2026-11-11')['revenue']);
        $this->assertSame('0', app(Report::class)->build('2026-11-15', '2026-11-15')['revenue']);
    }

    public function test_exact_large_aggregates_negative_profit_and_tooltip_strings(): void
    {
        $this->sale(qty: 2000000000, price: 4000000000, cost: 4294967295);
        $this->sale(qty: 2000000000, price: 4000000000, cost: 4294967295);
        $report = app(Report::class)->build('2026-11-11', '2026-11-11');
        $this->assertSame('16000000000000000000', $report['revenue']);
        $this->assertSame('17179869180000000000', $report['cost']);
        $this->assertSame('-1179869180000000000', $report['net']);
        $this->assertSame($report['revenue'], $report['days'][0]['amount']);
        $this->assertSame($report['revenue'], $report['highest']);
        $this->assertSame($report['revenue'], $report['average']);
        $this->assertTrue($report['axisTrillions']);
        $this->assertContains('Rp3.200.000 T', array_column($report['ticks'], 'label'));
        $product = Product::firstOrFail();
        foreach ([1, 2] as $unused) {
            StockEntry::create(['product_id' => $product->id, 'qty' => 2000000000, 'cost_price' => 4000000000, 'entry_date' => '2026-11-11', 'created_by' => $this->admin->id]);
        }
        $this->assertSame('16000000000000000000', app(Report::class)->build('2026-11-11', '2026-11-11')['restock']);
        $this->assertSame('−Rp1.179.869.180.000.000.000', Report::money($report['net']));
        Livewire::test(Index::class)->set('from', '2026-11-11')->set('to', '2026-11-11')->assertSeeText('Rp16.000.000.000.000.000.000')->assertSeeText('−Rp1.179.869.180.000.000.000')->assertSee('penjualan Rp16.000.000.000.000.000.000');
    }

    public function test_daily_zero_buckets_and_multiwindow_keep_whole_period_totals_and_average(): void
    {
        $this->sale('2026-10-31', price: 50, cost: 20);
        $this->sale('2026-11-01', price: 30, cost: 10);
        $this->sale('2026-11-02', price: 1, cost: 0);
        $first = app(Report::class)->build('2026-10-01', '2026-11-02');
        $last = app(Report::class)->build('2026-10-01', '2026-11-02', 1);
        $this->assertSame('81', $first['revenue']);
        $this->assertSame($first['revenue'], $last['revenue']);
        $this->assertSame('2', $first['average']);
        $this->assertSame('15', $first['averageRemainder']);
        $this->assertSame('50', $first['days'][30]['amount']);
        $this->assertSame('0', $first['days'][0]['amount']);
        $this->assertSame('2026-11-01', $last['days'][0]['date']);
        $this->assertCount(2, $last['days']);
        Livewire::test(Index::class)->set('from', '2026-10-01')->set('to', '2026-11-02')->assertSee('Hari berikutnya')->set('chartPage', 1)->assertSee('Grafik 2026-11-01–2026-11-02')->assertViewHas('report', fn ($report) => $report['revenue'] === '81')->call('period', 'month')->assertSet('chartPage', 0)->assertSet('from', '2026-11-01');
    }

    public function test_guest_technician_inactive_deleted_and_demoted_admin_block_before_report_queries(): void
    {
        auth()->logout();
        $this->get('/laporan')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'teknisi']));
        $this->get('/laporan')->assertForbidden();
        foreach (['demote', 'disable', 'delete'] as $mode) {
            $admin = User::factory()->create(['role' => 'admin']);
            $this->actingAs($admin);
            $component = Livewire::test(Index::class);
            match ($mode) {
                'demote' => $admin->update(['role' => 'teknisi']),
                'disable' => $admin->update(['is_active' => false]),
                default => $admin->delete(),
            };
            DB::enableQueryLog();
            DB::flushQueryLog();
            $component->call('period', 'today')->assertForbidden();
            $queries = implode(' ', array_column(DB::getQueryLog(), 'query'));
            $this->assertStringNotContainsString('service_orders', $queries);
            $this->assertStringNotContainsString('sales', $queries);
            $this->assertStringNotContainsString('expenses', $queries);
            DB::disableQueryLog();
        }
    }
}
