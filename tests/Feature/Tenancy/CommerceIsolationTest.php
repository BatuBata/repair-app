<?php

namespace Tests\Feature\Tenancy;

use App\Actions\Sales\CreateSale;
use App\Actions\Sales\DeleteSale;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Expenses\Index as Expenses;
use App\Livewire\Sales\Form as SaleForm;
use App\Livewire\Sales\Show as SaleShow;
use App\Livewire\Stock\Form as StockForm;
use App\Livewire\Stock\Index as Stock;
use App\Livewire\Stock\Restock;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StockEntry;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CommerceIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00:00', 'Asia/Makassar'));
    }

    private function activate(User $user): void
    {
        $this->actingAs($user);
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
    }

    /** @return array{User, Product, Sale, Expense, StockEntry, ServiceOrder} */
    private function shop(string $name, int $factor = 1): array
    {
        $tenant = Tenant::create(['name' => 'Tenant '.$name]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $this->activate($owner);
        StoreSetting::create(['name' => 'Identitas '.$name, 'address' => 'Alamat '.$name, 'warranty_terms' => 'Garansi '.$name, 'phone' => '6281234567890']);
        $product = Product::create(['name' => 'Produk '.$name, 'stock' => 5, 'cost_price' => 20 * $factor, 'sell_price' => 100 * $factor]);
        $entry = $product->stockEntries()->create(['qty' => 2, 'cost_price' => 20 * $factor, 'entry_date' => '2026-11-17', 'created_by' => $owner->id]);
        $sale = app(CreateSale::class)->handle($this->input($product, ['customer_name' => 'Customer '.$name]));
        $expense = Expense::create(['spent_on' => '2026-11-17', 'category' => 'bensin', 'description' => 'Biaya '.$name, 'amount' => 5 * $factor, 'created_by' => $owner->id]);
        $category = Category::create(['name' => 'Kategori '.$name]);
        $order = ServiceOrder::create(['number' => 'TTS-2611-001', 'category_id' => $category->id, 'created_by' => $owner->id, 'customer_name' => 'Servis '.$name, 'customer_phone' => '081234567890', 'complaint' => 'Keluhan '.$name, 'status' => 'selesai', 'invoiced_on' => '2026-11-17', 'warranty_days' => 2, 'warranty_until' => '2026-11-19']);
        $order->items()->create(['type' => 'part', 'description' => 'Jasa '.$name, 'qty' => 1, 'price' => 50 * $factor, 'cost' => 10 * $factor]);

        return [$owner, $product, $sale, $expense, $entry, $order];
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function input(Product $product, array $overrides = []): array
    {
        return [...['sold_on' => '2026-11-17', 'customer_phone' => '081234567890', 'warranty_days' => 2, 'items' => [['product_id' => $product->id, 'qty' => 1, 'price' => $product->sell_price]]], ...$overrides];
    }

    public function test_lists_forms_documents_and_actions_cannot_open_foreign_records(): void
    {
        [$a, $product, $sale, $expense] = $this->shop('A');
        [, $foreignProduct, $foreignSale, $foreignExpense] = $this->shop('B', 10);
        $this->activate($a);
        foreach ([['/stok', 'Produk A', 'Produk B'], ['/penjualan', 'Customer A', 'Customer B'], ['/pengeluaran', 'Biaya A', 'Biaya B']] as [$url, $own, $foreign]) {
            $this->get($url)->assertOk()->assertSee($own)->assertDontSee($foreign);
        }
        foreach (['stock.edit' => $foreignProduct->id, 'stock.restock' => $foreignProduct->id, 'sales.show' => $foreignSale->id, 'sales.print' => $foreignSale->id, 'sales.receipt.pdf' => $foreignSale->id, 'sales.pdf' => $foreignSale->id] as $route => $id) {
            $this->get(route($route, $id))->assertNotFound();
        }
        $this->get(route('sales.print', $sale->id))->assertOk()->assertSee('Identitas A')->assertDontSee('Identitas B');
        $this->get(route('sales.receipt.pdf', $sale->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('sales.pdf', $sale->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->activate($a);
        Livewire::test(Stock::class)->call('toggleActive', $foreignProduct->id)->assertNotFound();
        Livewire::test(SaleForm::class)->call('selectProduct', 0, $foreignProduct->id)->assertNotFound();
        Livewire::test(Expenses::class)->call('edit', $foreignExpense->id)->assertNotFound();
        Livewire::test(Expenses::class)->call('confirmDelete', $foreignExpense->id)->assertNotFound();
        try {
            app(DeleteSale::class)->handle($foreignSale->id);
            $this->fail('Foreign sale deleted.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseHas('products', ['id' => $foreignProduct->id, 'stock' => 4, 'is_active' => true]);
            $this->assertDatabaseHas('sales', ['id' => $foreignSale->id]);
            $this->assertDatabaseHas('expenses', ['id' => $foreignExpense->id, 'amount' => 50]);
        }
        Livewire::test(StockForm::class, ['id' => $product->id])->set('name', 'Produk A diubah')->call('save')->assertHasNoErrors();
        Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '2')->set('cost_price', '30')->call('save')->assertHasNoErrors();
        Livewire::test(Expenses::class)->call('edit', $expense->id)->set('amount', '7')->call('save')->assertHasNoErrors();
        $this->assertSame(6, $product->fresh()->stock);
        $this->assertSame(7, $expense->fresh()->amount);
        app(DeleteSale::class)->handle($sale->id);
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_tenant_switch_rechecks_locked_ids_before_mutating_mounted_forms(): void
    {
        [$a] = $this->shop('A');
        [$b, $product, $sale, $expense] = $this->shop('B');
        $edit = Livewire::test(StockForm::class, ['id' => $product->id]);
        $restock = Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '2')->set('cost_price', '30');
        $expenseEdit = Livewire::test(Expenses::class)->call('edit', $expense->id)->set('amount', '999');
        $expenseDelete = Livewire::test(Expenses::class)->call('confirmDelete', $expense->id);
        $saleDelete = Livewire::test(SaleShow::class, ['id' => $sale->id])->call('confirmDelete');
        $this->activate($a);
        $edit->set('name', 'Forged')->call('save')->assertNotFound();
        $restock->call('save')->assertNotFound();
        $expenseEdit->call('save')->assertNotFound();
        $expenseDelete->call('delete')->assertNotFound();
        $saleDelete->call('delete')->assertNotFound();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Produk B', 'stock' => 4]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'amount' => 5]);
        $this->assertDatabaseCount('stock_entries', 2);
    }

    public function test_mixed_foreign_product_payload_rolls_back_stock_sale_and_counter(): void
    {
        [$a, $product] = $this->shop('A');
        [, $foreignProduct] = $this->shop('B');
        $this->activate($a);
        $items = [['product_id' => $product->id, 'qty' => 1, 'price' => 100], ['product_id' => $foreignProduct->id, 'qty' => 1, 'price' => 100]];
        $before = DB::table('number_sequences')->get()->toArray();
        try {
            app(CreateSale::class)->handle($this->input($product, ['items' => $items, 'tenant_id' => $foreignProduct->tenant_id]));
            $this->fail('Foreign product accepted.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('items.1.product_id', $error->errors());
        }
        Livewire::test(SaleForm::class)->set('customer_phone', '081234567890')->set('items', array_map(fn ($item) => array_map('strval', $item), $items))->call('save')->assertHasErrors('items.1.product_id');
        $this->assertSame(4, $product->fresh()->stock);
        $this->assertSame(4, $foreignProduct->fresh()->stock);
        $this->assertSame(1, Sale::count());
        $this->assertDatabaseCount('sales', 2);
        $this->assertDatabaseCount('sale_items', 2);
        $this->assertEquals($before, DB::table('number_sequences')->get()->toArray());
    }

    public function test_relations_and_loaded_foreign_models_cannot_cross_tenants(): void
    {
        [$a, $product, $sale, $expense] = $this->shop('A');
        [$b, $foreignProduct, $foreignSale, $foreignExpense, $foreignEntry] = $this->shop('B');
        $foreignItem = $foreignSale->items()->firstOrFail();
        $this->activate($a);
        $entry = $product->stockEntries()->firstOrFail();
        $item = $sale->items()->firstOrFail();
        foreach ([[$sale, 'created_by', $b->id], [$expense, 'created_by', $b->id], [$entry, 'created_by', $b->id], [$entry, 'product_id', $foreignProduct->id], [$item, 'product_id', $foreignProduct->id], [$item, 'sale_id', $foreignSale->id]] as [$model, $field, $id]) {
            try {
                $model->fresh()->update([$field => $id]);
                $this->fail('Foreign relation accepted: '.$field);
            } catch (ValidationException $error) {
                $this->assertArrayHasKey($field, $error->errors());
            }
        }
        foreach ([$foreignProduct, $foreignSale, $foreignExpense] as $model) {
            try {
                $model->forceFill(['tenant_id' => $a->tenant_id])->save();
                $this->fail('Foreign root moved.');
            } catch (HttpException $error) {
                $this->assertSame(404, $error->getStatusCode());
            }
        }
        foreach ([[$foreignEntry, 'product_id', $product->id], [$foreignItem, 'sale_id', $sale->id]] as [$model, $field, $id]) {
            try {
                $model->update([$field => $id]);
                $this->fail('Foreign child moved.');
            } catch (ModelNotFoundException) {
                $this->assertNotSame($id, $model->fresh()->getAttribute($field));
            }
        }
        $this->assertDatabaseHas('products', ['id' => $foreignProduct->id, 'tenant_id' => $b->tenant_id, 'stock' => 4]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'created_by' => $a->id]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'created_by' => $a->id]);
        $this->assertDatabaseHas('stock_entries', ['id' => $entry->id, 'product_id' => $product->id, 'created_by' => $a->id]);
        $this->assertDatabaseHas('sale_items', ['id' => $item->id, 'product_id' => $product->id, 'sale_id' => $sale->id]);
    }

    public function test_dashboard_report_totals_and_warranties_only_use_current_tenant(): void
    {
        [$a, , $saleA, , , $orderA] = $this->shop('A');
        [$b, , $saleB, , , $orderB] = $this->shop('B', 10);
        foreach ([[$a, 1, $saleA, $orderA], [$b, 10, $saleB, $orderB]] as [$owner, $factor, $sale, $order]) {
            $this->activate($owner);
            $report = app(Report::class)->build('2026-11-17', '2026-11-17');
            foreach (['revenue' => 150, 'cost' => 30, 'gross' => 120, 'expenses' => 5, 'net' => 115, 'restock' => 40] as $key => $value) {
                $this->assertSame((string) ($value * $factor), $report[$key], $key);
            }
            $this->assertCount(3, $report['rows']);
            $this->assertSame((string) (150 * $factor), $report['days'][0]['amount']);
            Livewire::test(Dashboard::class)->assertViewHas('revenue', fn ($revenue) => $revenue['total'] === 'Rp'.number_format(150 * $factor, 0, ',', '.'))
                ->assertViewHas('chart', fn ($chart) => $chart['totals']['revenue'] === 'Rp'.number_format(150 * $factor, 0, ',', '.') && $chart['totals']['invoices'] === '1')
                ->assertViewHas('counts', fn ($counts) => (int) $counts['selesai'] === 1)
                ->assertViewHas('reminders', fn ($reminders) => $reminders->count() === 2 && $reminders->firstWhere('kind', 'sale')['id'] === $sale->id && $reminders->firstWhere('kind', 'service')['id'] === $order->id);
        }
        $this->activate($a);
        Livewire::test(Dashboard::class)->call('markContacted', 'sale', $saleB->id)->assertHasErrors('reminder');
        Livewire::test(Dashboard::class)->call('markContacted', 'service', $orderB->id)->assertHasErrors('reminder');
        $this->assertNull($saleB->fresh()->warranty_contacted_at);
        $this->assertNull($orderB->fresh()->warranty_contacted_at);
        StoreSetting::query()->update(['name' => '']);
        Livewire::test(Dashboard::class)->assertViewHas('reminders', fn ($rows) => $rows->every(fn ($row) => str_contains(rawurldecode($row['url']), 'Tenant A')))
            ->call('markContacted', 'sale', $saleA->id)->assertHasNoErrors();
        $this->assertNotNull($saleA->fresh()->warranty_contacted_at);
    }

    public function test_jl_number_and_missing_context_fail_closed(): void
    {
        [$a, $productA, $saleA] = $this->shop('A');
        [$b, $productB, $saleB] = $this->shop('B');
        $this->assertSame($saleA->number, $saleB->number);
        foreach ([[$a, $productA], [$b, $productB]] as [$owner, $product]) {
            $this->activate($owner);
            $this->assertStringEndsWith('-002', app(CreateSale::class)->handle($this->input($product))->number);
        }
        app(TenantContext::class)->forget();
        foreach ([Product::class, Sale::class, Expense::class] as $model) {
            $this->assertSame(0, $model::count());
            try {
                $model::create([]);
                $this->fail('Root created without tenant.');
            } catch (\LogicException) {
                $this->assertSame(0, $model::count());
            }
        }
        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseCount('sales', 4);
        $this->assertDatabaseCount('expenses', 2);
    }
}
