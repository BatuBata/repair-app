<?php

namespace Tests\QA;

use App\Actions\Sales\CreateSale;
use App\Actions\Sales\DeleteSale;
use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Expenses\Index as Expenses;
use App\Livewire\Invoice\Form as InvoiceForm;
use App\Livewire\Stock\Restock;
use App\Livewire\Tts\Detail;
use App\Livewire\Tts\Form as TtsForm;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class IndependentTest extends TestCase
{
    use RefreshDatabase;

    private function rejects(callable $call): void
    {
        try {
            $call();
            $this->fail('Expected validation rejection');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        }
    }

    public function test_service_lifecycle_with_customer_privacy_stale_correction_and_revocation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tech = User::factory()->create(['role' => 'teknisi']);
        $peer = User::factory()->create(['role' => 'teknisi']);
        $cat = Category::create(['name' => 'QA Laptop']);
        $this->actingAs($tech);
        $form = Livewire::test(TtsForm::class)->set('customer_name', 'Pelanggan QA')->call('save')->assertHasErrors(['customer_phone', 'category_id', 'complaint'])->assertSet('customer_name', 'Pelanggan QA');
        $form->set('customer_phone', '0812-3456-7890')->set('category_id', (string) $cat->id)->set('complaint', 'Keluhan sintetis')->set('customer_address', 'Alamat privat QA')->call('save')->assertHasNoErrors();
        $order = ServiceOrder::firstOrFail();
        $this->assertSame('6281234567890', $order->customer_phone);
        Livewire::test(TtsForm::class, ['id' => $order->id])->set('item_description', 'Perangkat diperbarui')->call('save')->assertHasNoErrors();
        $this->actingAs($peer)->get('/tts/'.$order->id.'/edit')->assertForbidden();
        $this->actingAs($admin);
        $action = app(ManageServiceOrder::class);
        $action->assign($order->id, $tech->id);
        $this->assertNotNull($order->fresh()->assigned_at);
        $this->actingAs($tech)->get('/tts/'.$order->id.'/edit')->assertForbidden();
        $this->actingAs($admin);
        $this->travelTo(CarbonImmutable::parse('2026-11-11 01:10:00', 'Asia/Makassar'));
        $rows = [['type' => 'part', 'description' => 'Part privat QA', 'qty' => 1, 'price' => 1000000, 'cost' => 712345], ['type' => 'jasa', 'description' => 'Jasa QA', 'qty' => 1, 'price' => 100000, 'cost' => 5000]];
        $action->invoice($order->id, ['rows' => $rows, 'warranty_days' => 30, 'unrepairable' => false], false, InvoiceLines::revision($order->fresh()));
        $order->refresh();
        $this->assertSame(0, $order->items()->where('type', 'jasa')->firstOrFail()->cost);
        $this->assertSame('2026-12-11', $order->warranty_until->toDateString());
        $this->assertSame(1100000, InvoiceLines::calculate($order->items()->get()->toArray())['price']);
        $this->rejects(fn () => $action->invoice($order->id, ['rows' => $rows, 'warranty_days' => 30, 'unrepairable' => false], false, InvoiceLines::revision($order->fresh())));
        $this->rejects(fn () => $action->cancel($order->id, 'Tidak boleh batal'));
        $this->travelTo(CarbonImmutable::parse('2026-11-15 10:00:00', 'Asia/Makassar'));
        $action->collect($order->id, '2026-11-15');
        $revision = InvoiceLines::revision($order->fresh());
        $rows[0]['price'] = 900000;
        $action->invoice($order->id, ['rows' => $rows, 'warranty_days' => 0, 'unrepairable' => true], true, $revision);
        $this->rejects(fn () => $action->invoice($order->id, ['rows' => $rows, 'warranty_days' => 0, 'unrepairable' => true], true, $revision));
        $order->refresh();
        $this->assertSame('2026-11-11', $order->invoiced_on->toDateString());
        $this->assertSame('2026-11-15', $order->picked_up_on->toDateString());
        $this->assertSame('diambil', $order->status->value);
        $url = $this->get('/tts/'.$order->id.'/whatsapp')->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $url);
        $message = rawurldecode($url);
        foreach ([$order->number, 'Tanpa garansi', '/t/'.$order->tracking_token, 'Rp1.000.000'] as $text) {
            $this->assertStringContainsString($text, $message);
        }
        $public = $this->get('/t/'.$order->tracking_token)->assertOk()->assertHeader('X-Robots-Tag', 'noindex')->assertHeader('Cache-Control', 'no-store, private')->assertSee('sudah diambil')->assertSee('Rp1.000.000');
        foreach (['712345', '712.345', '6281234567890', 'Alamat privat QA', 'wire:snapshot'] as $secret) {
            $public->assertDontSee($secret);
        }
        $this->get('/t/'.swap_case($order->tracking_token))->assertNotFound();
        $component = Livewire::test(InvoiceForm::class, ['id' => $order->id]);
        $detail = Livewire::test(Detail::class, ['id' => $order->id]);
        User::whereKey($admin->id)->update(['role' => 'teknisi']);
        $component->call('save')->assertForbidden();
        $detail->call('$refresh')->assertDontSee('712.345')->assertDontSee('1.000.000');
        foreach (['712345', '712.345', 'Part privat QA'] as $secret) {
            $this->assertStringNotContainsString($secret, json_encode($detail->snapshot));
        }
        foreach (['/stok', '/penjualan', '/pengeluaran', '/tts/'.$order->id.'/rincian', '/tts/'.$order->id.'/whatsapp'] as $path) {
            $this->get($path)->assertForbidden()->assertDontSee('712345');
        }User::whereKey($admin->id)->update(['is_active' => false]);
        $detail->call('$refresh')->assertForbidden();
    }

    public function test_unrepairable_check_fee_fifty_thousand_is_public_revenue_with_zero_jasa_cost(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tech = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($admin);
        $cat = Category::create(['name' => 'QA biaya cek']);
        $o = app(CreateServiceOrder::class)->create(['customer_name' => 'Pelanggan biaya cek', 'customer_phone' => '081234567890', 'category_id' => $cat->id, 'complaint' => 'Tidak dapat diperbaiki'], $tech);
        $action = app(ManageServiceOrder::class);
        $action->assign($o->id, $tech->id);
        $action->invoice($o->id, ['rows' => [['type' => 'jasa', 'description' => 'Biaya cek', 'qty' => 1, 'price' => 50000, 'cost' => 5000]], 'warranty_days' => 0, 'unrepairable' => true], false, InvoiceLines::revision($o->fresh()));
        $this->assertTrue($o->fresh()->unrepairable);
        $this->assertSame('selesai', $o->fresh()->status->value);
        $this->assertSame(50000, InvoiceLines::calculate($o->items()->get()->toArray())['price']);
        $this->assertSame(0, $o->items()->firstOrFail()->cost);
        $this->get('/t/'.$o->tracking_token)->assertOk()->assertSee('Rp50.000')->assertSee('tidak dapat diperbaiki')->assertSee('Tidak ada garansi')->assertDontSee('5.000');
    }

    public function test_stock_snapshot_expense_separation_and_single_restore(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $p = Product::create(['name' => 'QA stock', 'cost_price' => 100000, 'sell_price' => 150000, 'stock' => 3]);
        $input = ['sold_on' => '2026-11-11', 'customer_phone' => '081234567890', 'warranty_days' => 30, 'items' => [['product_id' => $p->id, 'qty' => 2, 'price' => 150000]]];
        $s = app(CreateSale::class)->handle($input);
        $this->assertSame(1, $p->fresh()->stock);
        $this->assertSame('2026-12-11', $s->warranty_until->toDateString());
        $this->rejects(fn () => app(CreateSale::class)->handle($input));
        $this->assertSame(1, Sale::count());
        Livewire::test(Restock::class, ['id' => $p->id])->set('qty', '2')->set('cost_price', '120000')->call('save')->assertHasNoErrors();
        $this->assertSame(3, $p->fresh()->stock);
        $this->assertSame(120000, $p->fresh()->cost_price);
        $this->assertSame(100000, $s->items()->firstOrFail()->cost);
        $this->assertSame(0, Expense::count());
        $p->update(['is_active' => false]);
        $this->rejects(fn () => app(CreateSale::class)->handle($input));
        app(DeleteSale::class)->handle($s->id);
        $this->assertSame(5, $p->fresh()->stock);
        $this->assertSame(0, $s->items()->count());
        try {
            app(DeleteSale::class)->handle($s->id);
            $this->fail('second deletion accepted');
        } catch (ModelNotFoundException) {
        }$this->assertSame(5, $p->fresh()->stock);
    }

    public function test_expenses_crud_inclusive_same_day_reversed_cleared_and_date_bounds(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([['2026-11-10', '10000'], ['2026-11-11', '20000'], ['2026-11-12', '40000']] as [$date,$amount]) {
            Livewire::test(Expenses::class)->call('create')->set('spent_on', $date)->set('category', 'bensin')->set('amount', $amount)->set('description', 'Sintetis '.$date)->call('save')->assertHasNoErrors();
        }
        $c = Livewire::test(Expenses::class)->set('from', '2026-11-10')->set('to', '2026-11-11')->assertSee('Rp30.000')->assertDontSee('Rp40.000');
        $c->set('from', '2026-11-11')->assertSee('Rp20.000')->assertDontSee('Rp10.000');
        $c->set('from', '2026-11-12')->assertDontSee('Rp20.000');
        $c->set('from', '')->assertDontSee('Rp20.000');
        $c->set('from', '2026-11-11')->assertSee('Rp20.000');
        foreach (['0', '-1', '1.5', '4294967296'] as $v) {
            Livewire::test(Expenses::class)->call('create')->set('category', 'bensin')->set('amount', $v)->call('save')->assertHasErrors('amount');
        }foreach (['0999-12-31', '10000-01-01', '2026-02-30'] as $date) {
            Livewire::test(Expenses::class)->call('create')->set('category', 'bensin')->set('amount', '1')->set('spent_on', $date)->call('save')->assertHasErrors('spent_on');
        }
        $e = Expense::whereDate('spent_on', '2026-11-11')->firstOrFail();
        Livewire::test(Expenses::class)->call('edit', $e->id)->set('amount', '25000')->call('save')->assertHasNoErrors();
        $this->assertSame(25000, $e->fresh()->amount);
        Livewire::test(Expenses::class)->call('confirmDelete', $e->id)->call('delete');
        $this->assertNull($e->fresh());
    }
}
function swap_case(string $token): string
{
    return implode('', array_map(fn ($c) => ctype_lower($c) ? strtoupper($c) : strtolower($c), str_split($token)));
}
