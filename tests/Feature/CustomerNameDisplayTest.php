<?php

namespace Tests\Feature;

use App\Actions\Sales\CreateSale;
use App\Livewire\Customers\Index as Customers;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Sales\Form as SaleForm;
use App\Livewire\Tts\Form as TtsForm;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Services\Documents\Document;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerNameDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00:00', 'Asia/Makassar'));
        $this->admin = User::factory()->create(['role' => 'admin', 'username' => 'qa.display', 'name' => 'operator kecil']);
        $this->actingAs($this->admin);
    }

    private function context(): void
    {
        app(TenantContext::class)->set(Tenant::findOrFail($this->admin->tenant_id));
    }

    private function fixture(string $name = 'RF samarinda'): array
    {
        $this->context();
        $customer = Customer::create(['name' => $name, 'phone' => '081234567890', 'address' => 'jalan kecil samarinda']);
        $category = Category::create(['name' => 'komputer kecil']);
        $product = Product::create(['name' => 'adaptor kecil', 'stock' => 10, 'cost_price' => 10000, 'sell_price' => 25000]);
        $order = app(CreateServiceOrder::class)->create(['customer_id' => $customer->id, 'customer_name' => $name, 'customer_phone' => $customer->phone, 'category_id' => $category->id, 'complaint' => 'tidak menyala'], $this->admin);
        $order->update(['status' => 'selesai', 'invoiced_on' => '2026-11-20', 'warranty_days' => 2, 'warranty_until' => '2026-11-22']);
        $order->items()->create(['type' => 'jasa', 'description' => 'pembersihan kecil', 'qty' => 1, 'price' => 25000, 'cost' => 10000]);
        $sale = app(CreateSale::class)->handle(['customer_id' => $customer->id, 'sold_on' => '2026-11-20', 'warranty_days' => 2, 'items' => [['product_id' => $product->id, 'qty' => 1, 'price' => 25000]]]);

        return [$customer, $order->refresh(), $sale, $product];
    }

    public function test_all_customer_views_reports_and_documents_format_names_without_changing_raw_records(): void
    {
        [$customer, $order, $sale] = $this->fixture();
        foreach ([route('customers.index'), route('customers.show', $customer->id), route('tts.index'), route('tts.show', $order->id), route('invoice.edit', $order->id), route('sales.index'), route('sales.show', $sale->id), route('tts.create'), route('sales.create')] as $url) {
            $this->get($url)->assertOk()->assertSee('RF Samarinda');
        }
        $this->get(route('customers.show', $customer->id))->assertSee('Pelanggan RF Samarinda')->assertSee('jalan kecil samarinda');
        $this->get(route('sales.show', $sale->id))->assertSee('adaptor kecil');
        $this->context();
        Livewire::test(Dashboard::class)->assertSee('RF Samarinda')->assertViewHas('reminders', fn ($rows) => $rows->count() === 2 && $rows->every(fn ($row) => $row['name'] === 'RF Samarinda'));
        $report = app(Report::class)->build('2026-11-20', '2026-11-20');
        foreach ($report['rows'] as $row) {
            $this->assertStringStartsWith('RF Samarinda', $row['description']);
        }
        $documents = app(Document::class);
        $this->assertSame('RF Samarinda', $documents->order($order->id, false, true)['customer']);
        $this->assertSame('RF Samarinda', $documents->sale($sale->id)['customer']);
        $this->get(route('tts.print', $order->id))->assertOk()->assertSee('RF Samarinda');
        $this->get(route('sales.print', $sale->id))->assertOk()->assertSee('RF Samarinda');
        $this->context();
        $this->assertSame('RF samarinda', $customer->fresh()->name);
        $this->assertSame('RF samarinda', $order->fresh()->customer_name);
        $this->assertSame('RF samarinda', $sale->fresh()->customer_name);
        $this->assertSame('qa.display', $this->admin->fresh()->username);
        $this->assertSame('operator kecil', $this->admin->fresh()->name);
    }

    public function test_existing_customer_edit_and_transaction_selection_keep_raw_name_when_saved(): void
    {
        [$customer, $order, $sale, $product] = $this->fixture('budi santoso');
        Livewire::test(Customers::class)->call('edit', $customer->id)->assertSet('name', 'budi santoso')->call('save')->assertHasNoErrors();
        Livewire::test(TtsForm::class, ['id' => $order->id])->assertSet('customer_name', 'budi santoso')->call('save')->assertHasNoErrors();
        Livewire::test(TtsForm::class)->assertSee('Budi Santoso')->set('customer_id', (string) $customer->id)->assertSet('customer_name', 'budi santoso')
            ->set('category_id', (string) $order->category_id)->set('complaint', 'mati')->call('save')->assertHasNoErrors();
        Livewire::test(SaleForm::class)->assertSee('Budi Santoso')->set('customer_id', (string) $customer->id)->assertSet('customer_name', 'budi santoso')
            ->call('selectProduct', 0, $product->id)->call('save')->assertHasNoErrors();
        $this->assertSame('budi santoso', $customer->fresh()->name);
        $this->assertSame('budi santoso', $order->fresh()->customer_name);
        $this->assertSame('budi santoso', $sale->fresh()->customer_name);
        $this->assertSame(['budi santoso'], ServiceOrder::pluck('customer_name')->unique()->values()->all());
        $this->assertSame(['budi santoso'], Sale::pluck('customer_name')->unique()->values()->all());
    }

    public function test_name_escaping_nullable_sale_fallback_and_public_tenant_privacy_are_preserved(): void
    {
        $raw = 'ibu <script>alert(1)</script> putri';
        [$customer, $order, $sale] = $this->fixture($raw);
        foreach ([route('customers.index'), route('customers.show', $customer->id), route('tts.show', $order->id), route('sales.show', $sale->id), route('tts.print', $order->id), route('sales.print', $sale->id)] as $url) {
            $this->get($url)->assertOk()->assertSee(ucwords($raw))->assertDontSee('<script>alert(1)</script>', false);
        }
        $this->context();
        $public = app(Document::class)->order($order->id, true, true, $order->tracking_token);
        $this->assertNull($public['customer']);
        $this->assertNull($public['phone']);
        $this->get(route('tracking.show', $order->tracking_token))->assertOk()->assertDontSee('Ibu &lt;script&gt;', false)->assertDontSee('jalan kecil samarinda')->assertDontSee('6281234567890');
        $this->context();
        $sale->update(['customer_name' => null]);
        $this->get(route('sales.index'))->assertOk()->assertSee('Customer umum');
        $this->get(route('sales.show', $sale->id))->assertOk()->assertSee('Customer umum');
        $this->context();
        $this->assertNull(app(Document::class)->sale($sale->id)['customer']);
        $rows = collect(app(Report::class)->build('2026-11-20', '2026-11-20')['rows']);
        $this->assertSame('Penjualan langsung', $rows->firstWhere('type', 'jual')['description']);
        $other = Tenant::create(['name' => 'Toko asing QA']);
        app(TenantContext::class)->set($other);
        $foreign = Customer::create(['name' => 'rahasia pelanggan asing', 'phone' => '081234567891']);
        $this->context();
        $this->get(route('customers.index'))->assertOk()->assertDontSee('Rahasia Pelanggan Asing');
        $this->get(route('customers.show', $foreign->id))->assertNotFound();
        $this->get(route('tts.create'))->assertOk()->assertDontSee('Rahasia Pelanggan Asing');
        $this->get(route('sales.create'))->assertOk()->assertDontSee('Rahasia Pelanggan Asing');
    }
}
