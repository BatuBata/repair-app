<?php

namespace Tests\Feature\Stock;

use App\Livewire\Stock\Form;
use App\Livewire\Stock\Index;
use App\Livewire\Stock\Restock;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StockTest extends TestCase
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
        return Product::create([...['name' => 'Kabel HDMI 2 meter', 'cost_price' => 25000, 'sell_price' => 40000, 'stock' => 3], ...$attributes]);
    }

    public function test_admin_creates_edits_and_toggles_product_without_resetting_current_stock(): void
    {
        $this->admin();
        Livewire::test(Form::class)->set('name', '  Kabel HDMI 2 meter  ')->set('cost_price', '25000')->set('sell_price', '40000')->set('stock', '3')
            ->call('save')->assertHasNoErrors()->assertRedirect(route('stock.index'));
        $product = Product::firstOrFail();
        $this->assertSame('Kabel HDMI 2 meter', $product->name);
        // A stock change from a concurrent sale/restock must survive an older edit form.
        $form = Livewire::test(Form::class, ['id' => $product->id]);
        $product->update(['stock' => 8]);
        $form->set('name', 'Kabel HDMI')->set('cost_price', '27000')->set('sell_price', '45000')->set('stock', '-1')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Kabel HDMI', 'stock' => 8, 'cost_price' => 27000, 'sell_price' => 45000]);
        Livewire::test(Index::class)->call('toggleActive', $product->id)->assertHasNoErrors();
        $this->assertFalse($product->fresh()->is_active);
        Livewire::test(Index::class)->call('toggleActive', $product->id)->assertHasNoErrors();
        $this->assertTrue($product->fresh()->is_active);
        $this->assertDatabaseCount('stock_entries', 0);
    }

    public function test_restock_updates_stock_and_cost_and_keeps_history_and_sale_snapshot_without_expense(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $sale = Sale::create(['number' => 'JL-2611-001', 'sold_on' => '2026-11-13', 'created_by' => $admin->id]);
        $item = SaleItem::create(['sale_id' => $sale->id, 'product_id' => $product->id, 'product_name' => $product->name, 'qty' => 2, 'price' => 40000, 'cost' => 25000]);
        $form = Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '2')->set('cost_price', '28000')
            ->set('entry_date', '2026-11-13')->set('note', 'Pemasok Balikpapan')->call('save')->assertHasNoErrors()->assertSee('Restok berhasil disimpan');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5, 'cost_price' => 28000, 'sell_price' => 40000]);
        $this->assertDatabaseHas('stock_entries', ['product_id' => $product->id, 'qty' => 2, 'cost_price' => 28000, 'created_by' => $admin->id, 'note' => 'Pemasok Balikpapan']);
        $entry = StockEntry::firstOrFail();
        $form->set('qty', '1')->set('cost_price', '31000')->set('entry_date', '2026-11-14')->call('save')->assertHasNoErrors()->assertSee('Pemasok Balikpapan')->assertSee('+2 unit');
        $this->assertSame(6, $product->fresh()->stock);
        $this->assertSame(28000, $entry->fresh()->cost_price);
        $this->assertSame(2, $entry->fresh()->qty);
        $this->assertSame('2026-11-13', $entry->fresh()->entry_date->toDateString());
        $this->assertSame(30000, $item->fresh()->subtotalPrice() - $item->fresh()->subtotalCost());
        Livewire::test(Form::class, ['id' => $product->id])->set('name', 'Nama baru')->set('cost_price', '35000')->set('sell_price', '50000')->call('save');
        Livewire::test(Index::class)->call('toggleActive', $product->id);
        $this->assertSame('Kabel HDMI 2 meter', $item->fresh()->product_name);
        $this->assertSame(40000, $item->fresh()->price);
        $this->assertSame(25000, $item->fresh()->cost);
        $this->assertDatabaseCount('stock_entries', 2);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertSame([], array_values(array_intersect(['edit', 'delete', 'destroy', 'update'], get_class_methods(Restock::class))));
    }

    public function test_invalid_create_data_and_unsigned_boundaries_do_not_create_product(): void
    {
        $this->admin();
        foreach (['-1', '1.5', '4294967296', 'bad', ''] as $invalid) {
            Livewire::test(Form::class)->set('name', 'Valid')->set('cost_price', $invalid)->set('sell_price', $invalid)->set('stock', $invalid)
                ->call('save')->assertHasErrors(['cost_price', 'sell_price', 'stock']);
        }
        Livewire::test(Form::class)->set('name', '   ')->set('cost_price', '0')->set('sell_price', '0')->call('save')->assertHasErrors('name');
        Livewire::test(Form::class)->set('name', str_repeat('a', 256))->set('cost_price', '0')->set('sell_price', '0')->call('save')->assertHasErrors('name');
        $this->assertDatabaseCount('products', 0);
        Livewire::test(Form::class)->set('name', str_repeat('a', 255))->set('cost_price', '4294967295')->set('sell_price', '0')->set('stock', '4294967295')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('products', ['cost_price' => 4294967295, 'sell_price' => 0, 'stock' => 4294967295]);
    }

    public function test_invalid_restock_data_and_overflow_leave_stock_cost_and_history_unchanged(): void
    {
        $this->admin();
        $product = $this->product();
        foreach (['0', '-1', '1.5', '4294967296', 'bad', ''] as $invalid) {
            Livewire::test(Restock::class, ['id' => $product->id])->set('qty', $invalid)->call('save')->assertHasErrors('qty');
        }
        foreach (['-1', '1.5', '4294967296', 'bad', ''] as $invalid) {
            Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '2')->set('cost_price', $invalid)->call('save')->assertHasErrors('cost_price');
        }
        Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '1')->set('entry_date', '2026-02-30')->set('note', str_repeat('a', 1001))->call('save')->assertHasErrors(['entry_date', 'note']);
        Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '4294967295')->set('cost_price', '0')->call('save')->assertHasErrors('qty');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3, 'cost_price' => 25000]);
        $this->assertDatabaseCount('stock_entries', 0);
        $product->update(['stock' => 0]);
        Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '4294967295')->set('cost_price', '0')->call('save')->assertHasNoErrors();
        $this->assertSame(4294967295, $product->fresh()->stock);
    }

    public function test_restock_uses_current_stock_and_rejects_deactivated_product(): void
    {
        $this->admin();
        $product = $this->product();
        $first = Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '2');
        $second = Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '4');
        $first->call('save')->assertHasNoErrors();
        $second->call('save')->assertHasNoErrors();
        $this->assertSame(9, $product->fresh()->stock);
        $product->update(['is_active' => false]);
        $second->set('qty', '1')->call('save')->assertHasErrors('qty');
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_entries', 2);
    }

    public function test_history_write_failure_rolls_back_stock_and_cost(): void
    {
        $this->admin();
        $product = $this->product();
        StockEntry::creating(fn () => throw new RuntimeException('synthetic history write failure'));
        try {
            Livewire::test(Restock::class, ['id' => $product->id])->set('qty', '2')->set('cost_price', '99000')->call('save');
            $this->fail('Expected synthetic history write failure.');
        } catch (RuntimeException $e) {
            $this->assertSame('synthetic history write failure', $e->getMessage());
        }
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3, 'cost_price' => 25000]);
        $this->assertDatabaseCount('stock_entries', 0);
    }

    public function test_routes_require_active_admin_and_components_reject_technician(): void
    {
        $product = $this->product();
        $paths = ['/stok', '/stok/baru', '/stok/'.$product->id.'/edit', '/stok/'.$product->id.'/restok'];
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $technician = User::factory()->create();
        $this->actingAs($technician);
        foreach ($paths as $path) {
            $this->get($path)->assertForbidden();
        }
        foreach ([Index::class, Form::class, Restock::class] as $component) {
            Livewire::test($component, ['id' => $product->id])->assertForbidden();
        }
        $admin = $this->admin();
        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
        $admin->update(['is_active' => false]);
        foreach ($paths as $path) {
            $this->actingAs($admin)->get($path)->assertForbidden();
        }
    }

    public function test_stale_demoted_or_inactive_admin_cannot_run_any_action_or_refresh(): void
    {
        $product = $this->product();
        foreach ([['role' => 'teknisi'], ['is_active' => false]] as $revocation) {
            foreach ([[Index::class, 'toggleActive'], [Index::class, '$refresh'], [Form::class, 'save'], [Form::class, '$refresh'], [Restock::class, 'save'], [Restock::class, '$refresh']] as [$class, $action]) {
                $admin = $this->admin();
                $component = Livewire::test($class, ['id' => $product->id]);
                User::whereKey($admin->id)->update($revocation);
                $component->call($action, $product->id)->assertForbidden();
            }
        }
        $this->assertTrue($product->fresh()->is_active);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_entries', 0);
    }

    public function test_invalid_ids_are_rejected_before_looking_up_products(): void
    {
        $this->admin();
        foreach (['invalid', 0, -1, [], '1 OR 1=1', '9223372036854775808'] as $id) {
            Livewire::test(Index::class)->call('toggleActive', $id)->assertHasErrors('id');
        }
        foreach ([Form::class, Restock::class] as $class) {
            Livewire::test($class, ['id' => 999])->assertNotFound();
        }
        Livewire::test(Index::class)->call('toggleActive', 999)->assertNotFound();
        $this->get('/stok/invalid/edit')->assertNotFound();
        $this->get('/stok/invalid/restok')->assertNotFound();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_edit_id_is_locked(): void
    {
        $this->admin();
        $product = $this->product();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Form::class, ['id' => $product->id])->set('productId', 999);
    }

    public function test_restock_id_is_locked(): void
    {
        $this->admin();
        $product = $this->product();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Restock::class, ['id' => $product->id])->set('productId', 999);
    }

    public function test_empty_state_and_escaped_names_notes_and_rupiah_display(): void
    {
        $admin = $this->admin();
        $this->get('/stok')->assertSee('Belum ada barang jualan.');
        $product = $this->product(['name' => '<script>alert(1)</script>']);
        StockEntry::create(['product_id' => $product->id, 'qty' => 2, 'cost_price' => 25000, 'entry_date' => '2026-11-13', 'note' => '<script>bad()</script>', 'created_by' => $admin->id]);
        $this->get('/stok')->assertSee('Rp25.000')->assertSee('Rp40.000')->assertSee($product->name)->assertDontSee($product->name, false);
        $this->get('/stok/'.$product->id.'/restok')->assertSee('<script>bad()</script>')->assertDontSee('<script>bad()</script>', false)->assertSee('+2 unit');
    }
}
