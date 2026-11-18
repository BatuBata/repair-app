<?php

namespace Tests\Feature\Customers;

use App\Actions\Sales\CreateSale;
use App\Livewire\Customers\Show;
use App\Livewire\Sales\Form as SaleForm;
use App\Livewire\Tts\Form;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CustomerRelationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    private function saleInput(): array
    {
        $product = Product::create(['name' => 'Kabel pelanggan', 'cost_price' => 1000, 'sell_price' => 2000, 'stock' => 10]);

        return ['sold_on' => '2026-11-18', 'customer_name' => '', 'customer_phone' => '', 'items' => [['product_id' => $product->id, 'qty' => 1, 'price' => 2000]]];
    }

    public function test_new_tts_links_customer_and_phone_changes_preserve_history_and_snapshots(): void
    {
        $this->admin();
        $category = Category::create(['name' => 'HP pelanggan']);
        Livewire::test(Form::class)->set('customer_name', 'Ratna')->set('customer_phone', '081234567890')
            ->set('customer_address', 'Alamat awal')->set('category_id', (string) $category->id)->set('complaint', 'Layar pelanggan')
            ->call('save')->assertHasNoErrors();
        $customer = Customer::firstOrFail();
        $order = ServiceOrder::firstOrFail();
        $this->assertSame($customer->id, $order->customer->id);
        $this->assertSame(1, $customer->serviceOrders()->count());
        Livewire::test(Form::class)->set('customer_id', (string) $customer->id)->set('category_id', (string) $category->id)
            ->set('complaint', 'TTS kedua terhubung')->call('save')->assertHasNoErrors();
        $this->assertSame(2, $customer->serviceOrders()->count());
        $customer->update(['name' => 'Ratna baru', 'phone' => '081234567899', 'address' => 'Alamat baru']);
        Livewire::test(Show::class, ['id' => $customer->id])->assertSee('Layar pelanggan')->assertSee('TTS kedua terhubung');
        $this->assertSame('Ratna', $order->fresh()->customer_name);
        $this->assertSame('6281234567890', $order->fresh()->customer_phone);
        $this->assertSame('Alamat awal', $order->fresh()->customer_address);
        Livewire::test(Form::class, ['id' => $order->id])->set('complaint', 'Diperbarui')->call('save')->assertHasNoErrors();
        $this->assertSame($customer->id, $order->fresh()->customer_id);
    }

    public function test_sales_select_registered_customer_or_register_complete_identity_and_allow_anonymous(): void
    {
        $this->admin();
        $input = $this->saleInput();
        $customer = Customer::create(['name' => 'Ratna', 'phone' => '081234567890']);
        Livewire::test(SaleForm::class)->set('customer_id', (string) $customer->id)->assertSet('customer_name', 'Ratna')
            ->set('customer_name', 'Tampered')->set('items', [['product_id' => (string) $input['items'][0]['product_id'], 'qty' => '1', 'price' => '2000']])
            ->call('save')->assertHasNoErrors();
        $sale = Sale::firstOrFail();
        $this->assertSame($customer->id, $sale->customer->id);
        $this->assertSame('Ratna', $sale->customer_name);
        $customer->update(['phone' => '081234567899', 'name' => 'Nama baru']);
        Livewire::test(Show::class, ['id' => $customer->id])->assertSee($sale->number)->assertSee('Kabel pelanggan');
        $this->assertSame('Ratna', $sale->fresh()->customer_name);
        $this->assertSame('6281234567890', $sale->fresh()->customer_phone);
        $this->assertSame(1, $customer->sales()->count());
        $new = app(CreateSale::class)->handle([...$input, 'customer_name' => 'Pelanggan baru', 'customer_phone' => '081234567898']);
        $this->assertNotNull($new->customer_id);
        $anonymous = app(CreateSale::class)->handle($input);
        $this->assertNull($anonymous->customer_id);
        $this->assertDatabaseCount('customers', 2);
        Livewire::test(SaleForm::class)->set('customer_id', (string) $customer->id)->call('newCustomer')
            ->assertSet('customer_id', '')->assertSet('customer_name', '')->assertSet('customer_phone', '');
    }

    public function test_customer_delete_nulls_relations_and_preserves_both_transaction_snapshots(): void
    {
        $user = $this->admin();
        $customer = Customer::create(['name' => 'Ratna', 'phone' => '081234567890']);
        $category = Category::create(['name' => 'TV']);
        $order = app(CreateServiceOrder::class)->create(['customer_id' => $customer->id, 'customer_name' => 'Ratna', 'customer_phone' => $customer->phone, 'category_id' => $category->id, 'complaint' => 'Mati'], $user)->refresh();
        $sale = app(CreateSale::class)->handle([...$this->saleInput(), 'customer_id' => $customer->id])->refresh();
        $orderBefore = [...$order->getAttributes(), 'customer_id' => null];
        $saleBefore = [...$sale->getAttributes(), 'customer_id' => null];
        $customer->delete();
        $this->assertSame($orderBefore, $order->fresh()->getAttributes());
        $this->assertSame($saleBefore, $sale->fresh()->getAttributes());
        $this->assertSame(1, $sale->items()->count());
    }

    public function test_customer_id_cannot_point_to_other_tenant_in_services_or_models(): void
    {
        $user = $this->admin();
        $input = $this->saleInput();
        $category = Category::create(['name' => 'HP']);
        $other = Tenant::create(['name' => 'Asing']);
        app(TenantContext::class)->set($other);
        $foreign = Customer::create(['name' => 'Asing', 'phone' => '081234567890']);
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
        foreach (['sale', 'tts', 'sale-model', 'tts-model'] as $kind) {
            try {
                $data = ['customer_id' => $foreign->id, 'customer_name' => 'Lokal', 'customer_phone' => '081234567890', 'category_id' => $category->id, 'complaint' => 'Mati', 'created_by' => $user->id];
                match ($kind) {
                    'sale' => app(CreateSale::class)->handle([...$input, 'customer_id' => $foreign->id]),
                    'tts' => app(CreateServiceOrder::class)->create($data, $user),
                    'sale-model' => Sale::create([...$data, 'number' => 'JL-FAKE', 'sold_on' => '2026-11-18']),
                    'tts-model' => ServiceOrder::create([...$data, 'number' => 'TTS-FAKE']),
                };
                $this->fail('Foreign customer must be rejected');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('customer_id', $exception->errors());
            }
        }
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('service_orders', 0);
        $this->assertSame(10, Product::firstOrFail()->stock);
    }

    public function test_failed_sale_rolls_back_customer_stock_and_document_sequence(): void
    {
        $this->admin();
        $input = [...$this->saleInput(), 'customer_name' => 'Ratna', 'customer_phone' => '081234567890'];
        Sale::creating(fn () => throw new RuntimeException('Synthetic failure'));
        try {
            app(CreateSale::class)->handle($input);
            $this->fail('Expected synthetic failure');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('number_sequences', 0);
        $this->assertSame(10, Product::firstOrFail()->stock);
    }

    public function test_migration_adds_only_nullable_links_without_backfilling_old_transactions(): void
    {
        $user = $this->admin();
        $category = Category::create(['name' => 'TV']);
        $order = app(CreateServiceOrder::class)->create(['customer_name' => 'Lama', 'customer_phone' => '081234567890', 'category_id' => $category->id, 'complaint' => 'Keluhan lama'], $user);
        $sale = app(CreateSale::class)->handle($this->saleInput());
        $migration = require database_path('migrations/2026_11_18_010000_add_customer_id_to_transactions.php');
        $migration->down();
        $before = [DB::table('service_orders')->where('id', $order->id)->first(), DB::table('sales')->where('id', $sale->id)->first()];
        $migration->up();
        foreach (['service_orders', 'sales'] as $i => $table) {
            $after = (array) DB::table($table)->first();
            $this->assertNull($after['customer_id']);
            unset($after['customer_id']);
            $this->assertEquals((array) $before[$i], $after);
        }
    }
}
