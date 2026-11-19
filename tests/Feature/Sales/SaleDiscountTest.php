<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\CreateSale;
use App\Actions\Sales\DeleteSale;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Sales\Form;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Documents\Document;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SaleDiscountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);
        $this->travelTo(CarbonImmutable::parse('2026-11-19 10:00:00', 'Asia/Makassar'));
    }

    private function product(array $attributes = []): Product
    {
        return Product::create([...['name' => 'Kabel HDMI', 'cost_price' => 25000, 'sell_price' => 40000, 'stock' => 20], ...$attributes]);
    }

    private function input(Product $product, array $attributes = []): array
    {
        return [...['sold_on' => '2026-11-19', 'customer_name' => 'Customer diskon', 'customer_phone' => '081234567891', 'items' => [['product_id' => (string) $product->id, 'qty' => '2', 'price' => '40000']]], ...$attributes];
    }

    public function test_invalid_discount_rolls_back_stock_customer_sale_and_counter(): void
    {
        $one = $this->product();
        $two = $this->product(['name' => 'USB', 'cost_price' => 5000]);
        foreach (['-1', '1.5', 'bad', '', null, [], '120001', (string) PHP_INT_MAX, '9223372036854775808', str_repeat('9', 100)] as $discount) {
            $input = $this->input($one, ['discount' => $discount]);
            $input['items'][] = ['product_id' => (string) $two->id, 'qty' => '1', 'price' => '40000'];
            try {
                app(CreateSale::class)->handle($input);
                $this->fail('Invalid discount accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('discount', $exception->errors());
            }
            $this->assertSame(20, $one->fresh()->stock);
            $this->assertSame(20, $two->fresh()->stock);
            foreach (['sales', 'sale_items', 'customers', 'number_sequences'] as $table) {
                $this->assertDatabaseCount($table, 0);
            }
        }
        $sale = app(CreateSale::class)->handle($this->input($one));
        $this->assertSame('JL-2611-001', $sale->number);
        $this->assertSame(0, $sale->fresh()->discount);
    }

    public function test_default_zero_and_full_discount_preserve_prices_cost_stock_and_payment_behavior(): void
    {
        $product = $this->product();
        $normal = app(CreateSale::class)->handle($this->input($product));
        $free = app(CreateSale::class)->handle($this->input($product, ['discount' => '80000']));
        $this->assertSame(0, $normal->discount);
        $this->assertSame(80000, $free->fresh()->discount);
        $this->assertSame('cash', $free->payment_method);
        $this->assertNotNull($free->paid_at);
        $line = $free->items()->firstOrFail();
        $this->assertSame(40000, $line->price);
        $this->assertSame(25000, $line->cost);
        $this->assertSame(80000, $line->subtotalPrice());
        $this->assertSame(16, $product->fresh()->stock);
        $this->assertSame(25000, $product->fresh()->cost_price);
        $data = app(Report::class)->build('2026-11-19', '2026-11-19');
        $freeRow = collect($data['rows'])->firstWhere('key', 'jual-'.$free->id);
        $this->assertSame('0', $freeRow['amount']);
        $this->assertSame('50000', $freeRow['cost']);
        $this->assertSame('-50000', $freeRow['profit']);
        $this->get(route('sales.print', $free->id))->assertOk()->assertSee('Rp0 (Gratis)');
        app(TenantContext::class)->set(Tenant::findOrFail($this->admin->tenant_id));
        app(DeleteSale::class)->handle($free->id);
        $this->assertSame(18, $product->fresh()->stock);
        $this->assertDatabaseMissing('sales', ['id' => $free->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $free->id]);
    }

    public function test_discount_above_uint32_and_full_php_integer_subtotal_are_exact(): void
    {
        $one = $this->product(['stock' => 4294967295, 'cost_price' => 0]);
        $two = $this->product(['cost_price' => 0]);
        $sale = app(CreateSale::class)->handle($this->input($one, [
            'discount' => (string) PHP_INT_MAX,
            'items' => [
                ['product_id' => (string) $one->id, 'qty' => '2147483648', 'price' => '4294967295'],
                ['product_id' => (string) $two->id, 'qty' => '1', 'price' => '2147483647'],
            ],
        ]));
        $this->assertSame(PHP_INT_MAX, $sale->fresh()->discount);
        $this->assertSame(PHP_INT_MAX, $sale->items()->get()->sum(fn ($line) => $line->subtotalPrice()));
        $row = app(Report::class)->build('2026-11-19', '2026-11-19')['rows'][0];
        $this->assertSame('0', $row['amount']);
        $this->assertSame((string) -PHP_INT_MAX, $row['lines'][2]['amount']);
    }

    public function test_form_preview_reacts_to_qty_price_discount_and_rejects_invalid_values(): void
    {
        $product = $this->product();
        $component = Livewire::test(Form::class)->assertSet('discount', '0')
            ->call('selectProduct', 0, $product->id)->set('items.0.qty', '2')
            ->set('discount', '5000')->assertViewHas('previewSubtotal', 80000)->assertViewHas('previewTotal', 75000)
            ->set('items.0.price', '35000')->assertViewHas('previewSubtotal', 70000)->assertViewHas('previewTotal', 65000)
            ->set('items.0.qty', '1')->assertViewHas('previewTotal', 30000);
        foreach (['', '-1', '1.5', '35001', '9223372036854775808'] as $invalid) {
            $component->set('discount', $invalid)->assertViewHas('previewTotal', null)->call('save')->assertHasErrors('discount');
        }
        Livewire::test(Form::class)->call('selectProduct', 0, $product->id)->set('items.0.price', '35000')
            ->set('discount', '35000')->assertViewHas('previewTotal', 0)->call('save')->assertHasNoErrors()->assertRedirect();
        $this->assertSame(35000, Sale::firstOrFail()->discount);
        Livewire::test(Form::class)->call('selectProduct', 0, $product->id)->set('items.0.price', 'bad')->assertViewHas('previewSubtotal', null)->assertViewHas('previewTotal', null);
    }

    public function test_reports_payment_banks_daily_dashboard_and_documents_use_discount_once_per_sale(): void
    {
        $one = $this->product();
        $two = $this->product(['name' => 'USB', 'cost_price' => 5000, 'sell_price' => 10000]);
        $input = $this->input($one, ['discount' => '15000', 'payment_method' => 'transfer', 'payment_bank' => 'Bank Mandiri']);
        $input['items'][] = ['product_id' => (string) $two->id, 'qty' => '3', 'price' => '10000'];
        $sale = app(CreateSale::class)->handle($input);
        app(CreateSale::class)->handle($this->input($one, ['sold_on' => '2026-10-31', 'discount' => '70000']));
        app(CreateSale::class)->handle($this->input($one, ['sold_on' => '2026-11-20', 'discount' => '70000']));
        $report = app(Report::class)->build('2026-11-19', '2026-11-19');
        foreach (['sales' => '95000', 'revenue' => '95000', 'cost' => '65000', 'gross' => '30000', 'net' => '30000'] as $key => $value) {
            $this->assertSame($value, $report[$key]);
        }
        $this->assertSame('95000', $report['payments']['transfer']);
        $this->assertSame('95000', $report['payments']['total']);
        $this->assertSame(['Bank Mandiri' => '95000'], $report['paymentBanks']);
        $this->assertSame('95000', $report['days'][0]['jual']);
        $this->assertSame('95000', $report['rows'][0]['amount']);
        $this->assertSame(['description' => 'Diskon', 'qty' => '1', 'amount' => '-15000', 'cost' => '0'], $report['rows'][0]['lines'][2]);
        Livewire::test(Dashboard::class)->assertViewHas('revenue', ['total' => 'Rp95.000', 'service' => 'Rp0', 'sales' => 'Rp95.000'])
            ->assertViewHas('chart', function ($chart) {
                $day = collect($chart['points'])->firstWhere('date', '2026-11-19');

                return $day['raw']['sales'] === '95000' && $day['raw']['sales_profit'] === '30000'
                    && $chart['totals']['revenue'] === 'Rp95.000' && $chart['totals']['profit'] === 'Rp30.000';
            });
        $document = app(Document::class)->sale($sale->id);
        $this->assertSame(15000, $document['discount']);
        $this->assertSame(40000, $document['items'][0]->price);
        $this->get(route('sales.show', $sale->id))->assertOk()->assertSee('Rp95.000')->assertSee('Rp30.000');
        $this->get(route('sales.index'))->assertOk()->assertSee('Rp95.000');
        $this->get(route('sales.print', $sale->id))->assertOk()->assertSee('Subtotal')->assertSee('Diskon')->assertSee('Rp110.000')->assertSee('−Rp15.000')->assertSee('Rp95.000');
        foreach (['sales.pdf', 'sales.receipt.pdf'] as $route) {
            $response = $this->get(route($route, $sale->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    public function test_discount_does_not_cross_tenant_and_existing_auth_is_required(): void
    {
        $tenantA = Tenant::findOrFail($this->admin->tenant_id);
        $product = $this->product();
        $sale = app(CreateSale::class)->handle($this->input($product, ['discount' => '5000']));
        $other = User::factory()->create(['tenant_id' => Tenant::create(['name' => 'Other store', 'slug' => 'discount-other'])->id, 'role' => 'admin']);
        $this->actingAs($other);
        app(TenantContext::class)->set(Tenant::findOrFail($other->tenant_id));
        $this->assertSame('0', app(Report::class)->build('2026-11-19', '2026-11-19')['sales']);
        Livewire::test(Dashboard::class)->assertViewHas('revenue', ['total' => 'Rp0', 'service' => 'Rp0', 'sales' => 'Rp0']);
        $this->get(route('sales.show', $sale->id))->assertNotFound();
        $this->get(route('sales.print', $sale->id))->assertNotFound();
        app(TenantContext::class)->set(Tenant::findOrFail($other->tenant_id));
        try {
            app(CreateSale::class)->handle($this->input($product, ['discount' => '5000']));
            $this->fail('Foreign product accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items.0.product_id', $exception->errors());
        }
        $this->actingAs($this->admin);
        app(TenantContext::class)->set($tenantA);
        $this->assertSame(18, $product->fresh()->stock);
        User::whereKey($this->admin->id)->update(['role' => 'teknisi']);
        Livewire::test(Form::class)->assertForbidden();
    }

    public function test_model_rejects_invalid_raw_discount_and_defaults_legacy_callers_to_zero(): void
    {
        $sale = new Sale(['number' => 'JL-MODEL', 'sold_on' => '2026-11-19', 'created_by' => $this->admin->id]);
        $this->assertSame(0, $sale->discount);
        foreach (['-1', '1.5', '9223372036854775808', null] as $discount) {
            $sale->discount = $discount;
            try {
                $sale->save();
                $this->fail('Invalid model discount accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('discount', $exception->errors());
            }
        }
        $sale->discount = '0';
        $sale->save();
        $this->assertSame(0, $sale->fresh()->discount);
    }

    public function test_migration_preserves_legacy_fields_and_blocks_discount_loss_on_rollback(): void
    {
        $sale = app(CreateSale::class)->handle($this->input($this->product()));
        $migration = require database_path('migrations/2026_11_19_020000_add_sale_discount.php');
        $migration->down();
        $before = (array) DB::table('sales')->where('id', $sale->id)->first();
        $migration->up();
        $after = (array) DB::table('sales')->where('id', $sale->id)->first();
        $this->assertSame(0, $after['discount']);
        unset($after['discount']);
        $this->assertSame($before, $after);
        DB::table('sales')->where('id', $sale->id)->update(['discount' => 1]);
        try {
            $migration->down();
            $this->fail('Rollback lost an existing discount.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('Rollback ditolak', $exception->getMessage());
        }
        $this->assertTrue(Schema::hasColumn('sales', 'discount'));
        $this->assertSame(1, DB::table('sales')->where('id', $sale->id)->value('discount'));
    }
}
