<?php

namespace Tests\Feature\Sales;

use App\Actions\Sales\CreateSale;
use App\Actions\Sales\DeleteSale;
use App\Livewire\Sales\Form;
use App\Livewire\Sales\Index;
use App\Livewire\Sales\Show;
use App\Livewire\Stock\Restock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SalesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        return $admin;
    }

    private function product(array $attributes = []): Product
    {
        return Product::create([...['name' => 'Kabel HDMI', 'cost_price' => 25000, 'sell_price' => 40000, 'stock' => 3], ...$attributes]);
    }

    private function input(Product $product, array $attributes = []): array
    {
        return [...['sold_on' => '2026-11-13', 'customer_name' => '', 'customer_phone' => '', 'warranty_days' => '0', 'items' => [['product_id' => (string) $product->id, 'qty' => '2', 'price' => '40000']]], ...$attributes];
    }

    private function invalid(array $input, string $key): void
    {
        try {
            app(CreateSale::class)->handle($input);
            $this->fail('Expected validation failure for '.$key);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($key, $e->errors());
        }
    }

    public function test_create_snapshots_stocks_and_immutable_sale_after_restock_and_product_changes(): void
    {
        $admin = $this->admin();
        $this->travelTo(CarbonImmutable::parse('2026-11-13 08:00:00', 'Asia/Makassar'));
        $product = $this->product();
        $sale = app(CreateSale::class)->handle($this->input($product));
        $this->assertSame('JL-2611-001', $sale->number);
        $this->assertSame($admin->id, $sale->created_by);
        $this->assertSame(1, $product->fresh()->stock);
        $item = $sale->items()->firstOrFail();
        $this->assertSame('Kabel HDMI', $item->product_name);
        $this->assertSame(40000, $item->price);
        $this->assertSame(25000, $item->cost);
        $this->assertSame(80000, $item->subtotalPrice());
        Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '2')->set('cost_price', '30000')->call('save')->assertHasNoErrors();
        $product->update(['name' => 'Nama berubah', 'sell_price' => 50000]);
        $this->assertSame(30000, $product->fresh()->cost_price);
        $this->assertSame('Kabel HDMI', $item->fresh()->product_name);
        $this->assertSame(25000, $item->fresh()->cost);
        $this->assertSame(40000, $item->fresh()->price);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_number_uses_wita_creation_month_and_does_not_reuse_deleted_number(): void
    {
        $this->admin();
        $this->travelTo(CarbonImmutable::parse('2026-11-30T16:05:00Z'));
        $product = $this->product();
        $sale = app(CreateSale::class)->handle($this->input($product, ['sold_on' => '2025-01-01']));
        $this->assertSame('JL-2612-001', $sale->number);
        $this->assertSame('2025-01-01', $sale->sold_on->toDateString());
        app(DeleteSale::class)->handle($sale->id);
        $next = app(CreateSale::class)->handle($this->input($product));
        $this->assertSame('JL-2612-002', $next->number);
    }

    public function test_creation_timestamp_is_pinned_with_number_when_clock_crosses_wita_month_during_insert(): void
    {
        $this->admin();
        $product = $this->product();
        $this->travelTo(CarbonImmutable::parse('2026-11-30 23:59:59', 'Asia/Makassar'));
        Sale::creating(fn () => $this->travelTo(CarbonImmutable::parse('2026-12-01 00:00:01', 'Asia/Makassar')));
        $sale = app(CreateSale::class)->handle($this->input($product));
        $this->assertSame('JL-2611-001', $sale->number);
        $this->assertSame('2026-11-30 23:59:59', $sale->created_at->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s'));
    }

    public function test_shortage_and_late_invalid_row_roll_back_every_product_and_document_number(): void
    {
        $this->admin();
        $one = $this->product();
        $two = $this->product(['stock' => 1]);
        $input = $this->input($one);
        $input['items'][] = ['product_id' => (string) $two->id, 'qty' => '2', 'price' => '40000'];
        $this->invalid($input, 'items.1.qty');
        $this->assertSame(3, $one->fresh()->stock);
        $this->assertSame(1, $two->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('number_sequences', 0);
        app(CreateSale::class)->handle($this->input($one));
        $this->invalid($this->input($one), 'items.0.qty');
        $this->assertSame(1, $one->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_duplicate_inactive_and_forged_products_are_rejected_without_partial_sale(): void
    {
        $this->admin();
        $product = $this->product();
        $input = $this->input($product);
        $input['items'][] = $input['items'][0];
        $this->invalid($input, 'items.0.product_id');
        foreach (['999', '-1', 'bad', [], '9223372036854775808'] as $id) {
            $input = $this->input($product);
            $input['items'][0]['product_id'] = $id;
            $this->invalid($input, 'items.0.product_id');
        }
        $product->update(['is_active' => false]);
        $this->invalid($this->input($product), 'items.0.product_id');
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_qty_and_money_validate_unsigned_integers_and_zero_price_is_valid(): void
    {
        $this->admin();
        $product = $this->product();
        foreach (['-1', '1.5', '4294967296', 'bad', [], ''] as $invalid) {
            foreach (['qty', 'price'] as $field) {
                $input = $this->input($product);
                $input['items'][0][$field] = $invalid;
                $this->invalid($input, 'items.0.'.$field);
            }
        }
        $input = $this->input($product);
        $input['items'][0]['qty'] = '0';
        $this->invalid($input, 'items.0.qty');
        $input['items'][0] = ['product_id' => (string) $product->id, 'qty' => '1', 'price' => '0'];
        $sale = app(CreateSale::class)->handle($input);
        $this->assertSame(0, $sale->items()->firstOrFail()->price);
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_line_and_accumulated_totals_cannot_overflow_php_integer(): void
    {
        $this->admin();
        $one = $this->product(['stock' => 4294967295, 'cost_price' => 4294967295]);
        $two = $this->product(['stock' => 4294967295, 'cost_price' => 4294967295]);
        $input = $this->input($one);
        $input['items'][0]['qty'] = '4294967295';
        $input['items'][0]['price'] = '4294967295';
        $this->invalid($input, 'items.0.qty');
        $input['items'][0]['price'] = '0';
        $this->invalid($input, 'items.0.qty');
        $input['items'][0] = ['product_id' => (string) $one->id, 'qty' => '2147483648', 'price' => '4294967295'];
        $input['items'][] = ['product_id' => (string) $two->id, 'qty' => '2147483648', 'price' => '4294967295'];
        $this->invalid($input, 'items.1.qty');
        $this->assertSame(4294967295, $one->fresh()->stock);
        $this->assertSame(4294967295, $two->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $input['items'] = [$input['items'][0]];
        $sale = app(CreateSale::class)->handle($input);
        $this->assertSame(9223372034707292160, $sale->items()->firstOrFail()->subtotalPrice());
    }

    public function test_warranty_normalizes_phone_and_computes_date_while_zero_has_no_expiry(): void
    {
        $this->admin();
        $product = $this->product(['stock' => 10]);
        $sale = app(CreateSale::class)->handle($this->input($product, ['customer_name' => '  Ratna  ', 'customer_phone' => '0812-3456 7890', 'warranty_days' => '30']));
        $this->assertSame('Ratna', $sale->customer_name);
        $this->assertSame('6281234567890', $sale->customer_phone);
        $this->assertSame('2026-12-13', $sale->warranty_until->toDateString());
        $zero = app(CreateSale::class)->handle($this->input($product, ['warranty_days' => '0']));
        $this->assertNull($zero->warranty_until);
        $this->assertNull($zero->customer_phone);
        $this->assertNull($zero->customer_name);
        $this->invalid($this->input($product, ['warranty_days' => '30']), 'customer_phone');
        $this->invalid($this->input($product, ['customer_phone' => 'bad']), 'customer_phone');
        $this->invalid($this->input($product, ['warranty_days' => '-1']), 'warranty_days');
        $this->invalid($this->input($product, ['warranty_days' => '1.5']), 'warranty_days');
        $this->invalid($this->input($product, ['warranty_days' => '4294967295', 'customer_phone' => '081234567890']), 'warranty_days');
        $this->invalid($this->input($product, ['sold_on' => '2026-02-30']), 'sold_on');
    }

    public function test_delete_restores_all_products_even_inactive_and_only_once(): void
    {
        $this->admin();
        $one = $this->product();
        $two = $this->product();
        $input = $this->input($one);
        $input['items'][] = ['product_id' => (string) $two->id, 'qty' => '3', 'price' => '50000'];
        $sale = app(CreateSale::class)->handle($input);
        $one->update(['is_active' => false]);
        app(DeleteSale::class)->handle($sale->id);
        $this->assertSame(3, $one->fresh()->stock);
        $this->assertSame(3, $two->fresh()->stock);
        $this->assertFalse($one->fresh()->is_active);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        try {
            app(DeleteSale::class)->handle($sale->id);
            $this->fail('Expected no second restore.');
        } catch (ModelNotFoundException) {
            $this->assertSame(3, $one->fresh()->stock);
        }
    }

    public function test_restore_overflow_rolls_back_prior_product_and_keeps_sale(): void
    {
        $this->admin();
        $one = $this->product();
        $two = $this->product();
        $input = $this->input($one);
        $input['items'][] = ['product_id' => (string) $two->id, 'qty' => '1', 'price' => '40000'];
        $sale = app(CreateSale::class)->handle($input);
        $two->update(['stock' => 4294967295]);
        try {
            app(DeleteSale::class)->handle($sale->id);
            $this->fail('Expected restore overflow validation.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('sale', $e->errors());
        }
        $this->assertSame(1, $one->fresh()->stock);
        $this->assertSame(4294967295, $two->fresh()->stock);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
        $this->assertDatabaseCount('sale_items', 2);
    }

    public function test_item_write_failure_rolls_back_stock_sale_and_sequence(): void
    {
        $this->admin();
        $product = $this->product();
        SaleItem::creating(fn () => throw new RuntimeException('synthetic item failure'));
        try {
            app(CreateSale::class)->handle($this->input($product));
            $this->fail('Expected synthetic item write failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('synthetic item failure', $e->getMessage());
        }
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('number_sequences', 0);
    }

    public function test_form_defaults_price_handles_rows_preview_and_saves(): void
    {
        $this->admin();
        $product = $this->product();
        $form = Livewire::test(Form::class)->call('selectProduct', 0, $product->id)->assertSet('items.0.price', '40000')->assertViewHas('previewTotal', 40000);
        $form->call('addItem')->assertCount('items', 2)->call('removeItem', 1)->assertCount('items', 1);
        $form->set('items.0.qty', '2')->assertViewHas('previewTotal', 80000)->set('items.0.price', '150000')->call('save')->assertHasNoErrors()->assertRedirect(route('sales.show', Sale::firstOrFail()->id));
        $this->assertDatabaseHas('sale_items', ['product_id' => $product->id, 'qty' => 2, 'price' => 150000, 'cost' => 25000]);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_form_preview_safely_handles_invalid_and_overflow_values(): void
    {
        $this->admin();
        $product = $this->product();
        $form = Livewire::test(Form::class)->call('selectProduct', 0, $product->id);
        foreach (['0', '-1', 'bad', '4294967296'] as $qty) {
            $form->set('items.0.qty', $qty)->assertViewHas('previewTotal', null);
        }
        $form->set('items.0.qty', '4294967295')->set('items.0.price', '4294967295')->assertViewHas('previewTotal', null);
        $form->set('items.0.qty', '1')->set('items.0.price', '0')->assertViewHas('previewTotal', 0);
    }

    public function test_form_and_delete_ids_are_validated_and_show_id_is_locked(): void
    {
        $this->admin();
        $product = $this->product();
        foreach (['bad', -1, []] as $invalid) {
            Livewire::test(Form::class)->call('selectProduct', 0, $invalid)->assertHasErrors('id');
            Livewire::test(Form::class)->call('removeItem', $invalid)->assertHasErrors('index');
            try {
                app(DeleteSale::class)->handle($invalid);
                $this->fail('Expected invalid delete ID.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('id', $e->errors());
            }
        }
        Livewire::test(Form::class)->call('removeItem', 99)->assertNotFound();
        Livewire::test(Form::class)->call('selectProduct', 0, 999)->assertNotFound();
        $sale = app(CreateSale::class)->handle($this->input($product));
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Show::class, ['id' => $sale->id])->set('saleId', 999);
    }

    public function test_delete_requires_explicit_confirmation_and_restores_stock(): void
    {
        $this->admin();
        $product = $this->product();
        $sale = app(CreateSale::class)->handle($this->input($product));
        Livewire::test(Show::class, ['id' => $sale->id])->call('delete')->assertStatus(422);
        Livewire::test(Show::class, ['id' => $sale->id])->call('confirmDelete')->assertSet('confirmingDelete', true)->call('cancelDelete')->assertSet('confirmingDelete', false);
        Livewire::test(Show::class, ['id' => $sale->id])->call('confirmDelete')->call('delete')->assertRedirect(route('sales.index'));
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_routes_and_actions_require_current_active_admin_including_stale_sessions(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $sale = app(CreateSale::class)->handle($this->input($product));
        $paths = ['/penjualan', '/penjualan/baru', '/penjualan/'.$sale->id];
        auth()->logout();
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        foreach ($paths as $path) {
            $this->get($path)->assertForbidden();
        }
        foreach ([['role' => 'teknisi'], ['is_active' => false]] as $revocation) {
            foreach ([[Index::class, '$refresh'], [Form::class, 'addItem'], [Form::class, 'removeItem'], [Form::class, 'selectProduct'], [Form::class, 'save'], [Form::class, '$refresh'], [Show::class, 'confirmDelete'], [Show::class, 'cancelDelete'], [Show::class, 'delete'], [Show::class, '$refresh']] as [$class, $action]) {
                $admin = $this->admin();
                $component = Livewire::test($class, ['id' => $sale->id]);
                User::whereKey($admin->id)->update($revocation);
                $component->call($action, 0, $product->id)->assertForbidden();
            }
        }
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertDatabaseCount('sales', 1);
    }

    public function test_direct_actions_reject_technician_or_revoked_actor(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $sale = app(CreateSale::class)->handle($this->input($product));
        User::whereKey($admin->id)->update(['role' => 'teknisi']);
        foreach ([fn () => app(CreateSale::class)->handle($this->input($product)), fn () => app(DeleteSale::class)->handle($sale->id)] as $action) {
            try {
                $action();
                $this->fail('Expected current-role403.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_empty_escaped_history_and_no_edit_or_print_routes(): void
    {
        $this->admin();
        $this->get('/penjualan')->assertOk()->assertSee('Belum ada penjualan.');
        $product = $this->product(['name' => '<script>bad()</script>']);
        $sale = app(CreateSale::class)->handle($this->input($product, ['customer_name' => '<script>customer()</script>']));
        $this->get('/penjualan/'.$sale->id)->assertOk()->assertSee($product->name)->assertDontSee($product->name, false)->assertSee('Rp80.000')->assertSee('Tanpa garansi')->assertDontSee('Cetak');
        $this->get('/penjualan/'.$sale->id.'/edit')->assertNotFound();
        $this->get('/penjualan/invalid')->assertNotFound();
        $this->get('/penjualan/999')->assertNotFound();
        $this->assertDatabaseCount('sale_items', 1);
    }
}
