<?php

namespace Tests\Feature\Settings;

use App\Actions\Sales\CreateSale;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Reports\Index as Reports;
use App\Livewire\Settings\Banks;
use App\Models\Bank;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\Payment;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BanksTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    public function test_defaults_start_active_and_can_only_be_toggled_for_the_current_store(): void
    {
        $user = $this->admin();
        $this->assertSame(Payment::BANKS, array_column(Payment::bankOptions(), 'value'));
        Livewire::test(Banks::class)->call('toggleDefault', 'BCA')->assertHasNoErrors();
        $this->assertNotContains('BCA', array_column(Payment::bankOptions(), 'value'));
        $default = Bank::firstOrFail();
        Livewire::test(Banks::class)->call('edit', $default->id)->assertForbidden();
        Livewire::test(Banks::class)->call('delete', $default->id)->assertForbidden();
        $other = Tenant::create(['name' => 'Toko B']);
        app(TenantContext::class)->set($other);
        $this->assertContains('BCA', array_column(Payment::bankOptions(), 'value'));
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
        Livewire::test(Banks::class)->call('toggleDefault', 'BCA')->assertHasNoErrors();
        $this->assertContains('BCA', array_column(Payment::bankOptions(), 'value'));
        Livewire::test(Banks::class)->call('toggleDefault', '')->assertHasErrors('name');
    }

    public function test_admin_can_manage_only_custom_banks_and_reserved_names_are_rejected(): void
    {
        $this->admin();
        $form = Livewire::test(Banks::class)->call('create');
        foreach (['', ' bca ', 'Semua', str_repeat('x', 51)] as $name) {
            $form->set('name', $name)->call('save')->assertHasErrors('name');
        }
        $form->set('name', 'Bank Jago')->call('save')->assertHasNoErrors();
        $bank = Bank::firstOrFail();
        $this->assertContains('Bank Jago', array_column(Payment::bankOptions(), 'value'));
        Livewire::test(Banks::class)->call('create')->set('name', 'Bank Jago')->call('save')->assertHasErrors('name');
        Livewire::test(Banks::class)->call('edit', $bank->id)->set('name', 'Bank Seabank')->call('save')->assertHasNoErrors();
        $this->assertSame('Bank Seabank', $bank->fresh()->name);
        Livewire::test(Banks::class)->call('toggle', $bank->id)->assertHasNoErrors();
        $this->assertNotContains('Bank Seabank', array_column(Payment::bankOptions(), 'value'));
        Livewire::test(Banks::class)->call('delete', $bank->id)->assertHasNoErrors();
        $this->assertDatabaseCount('banks', 0);
    }

    public function test_default_bank_cannot_be_deleted_by_changing_the_unsaved_model_name(): void
    {
        $this->admin();
        $bank = Bank::create(['name' => 'BCA', 'is_active' => false]);
        $bank->name = 'Nama lain';
        try {
            $bank->delete();
            $this->fail('Bank bawaan harus tetap dilindungi.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertDatabaseHas('banks', ['id' => $bank->id, 'name' => 'BCA']);
    }

    public function test_bank_actions_require_active_admin_and_do_not_accept_foreign_ids(): void
    {
        $this->get('/pengaturan/bank')->assertRedirect('/login');
        $user = $this->admin();
        $local = Bank::create(['name' => 'Bank lokal']);
        $other = Tenant::create(['name' => 'Toko B']);
        app(TenantContext::class)->set($other);
        $foreign = Bank::create(['name' => 'Bank privat B']);
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
        foreach (['edit', 'toggle', 'delete'] as $action) {
            Livewire::test(Banks::class)->assertDontSee('Bank privat B')->call($action, $foreign->id)->assertNotFound();
        }
        $this->assertNotContains('Bank privat B', array_column(Payment::bankOptions(all: true), 'value'));
        Livewire::test(Banks::class)->call('create')->set('name', 'Bank privat B')->call('save')->assertHasNoErrors();
        $editor = Livewire::test(Banks::class)->call('edit', $local->id)->set('name', 'Tidak boleh');
        User::whereKey($user->id)->update(['role' => 'teknisi']);
        $editor->call('save')->assertForbidden();
        $this->get('/pengaturan/bank')->assertForbidden();
        $this->assertDatabaseHas('banks', ['id' => $local->id, 'name' => 'Bank lokal']);
    }

    public function test_inactive_bank_is_rejected_at_payment_time_without_changing_stock_or_service(): void
    {
        $user = $this->admin();
        $product = Product::create(['name' => 'Kabel', 'cost_price' => 1000, 'sell_price' => 2000, 'stock' => 5]);
        $category = Category::create(['name' => 'HP']);
        $order = app(CreateServiceOrder::class)->create(['customer_name' => 'Ratna', 'customer_phone' => '081234567890', 'category_id' => $category->id, 'complaint' => 'Mati'], $user);
        $order->update(['status' => 'selesai', 'invoiced_on' => '2026-11-18']);
        $order->items()->create(['type' => 'jasa', 'description' => 'Pemeriksaan', 'qty' => 1, 'price' => 10000, 'cost' => 0]);
        Livewire::test(Banks::class)->call('toggleDefault', 'BCA');
        foreach (['sale', 'service'] as $type) {
            try {
                $payment = ['payment_method' => 'transfer', 'payment_bank' => 'BCA'];
                if ($type === 'sale') {
                    app(CreateSale::class)->handle(['sold_on' => '2026-11-18', 'items' => [['product_id' => $product->id, 'qty' => 1, 'price' => 2000]], ...$payment]);
                } else {
                    app(ManageServiceOrder::class)->recordPayment($order->id, $payment);
                }
                $this->fail('Bank nonaktif harus ditolak.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('payment_bank', $exception->errors());
            }
        }
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNull($order->fresh()->paid_at);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_custom_bank_snapshots_and_report_filters_survive_rename_delete_and_deactivation(): void
    {
        $user = $this->admin();
        $bank = Bank::create(['name' => 'Bank Jago, Syariah']);
        $product = Product::create(['name' => 'Kabel', 'cost_price' => 1000, 'sell_price' => 2000, 'stock' => 5]);
        $sale = app(CreateSale::class)->handle(['sold_on' => '2026-11-18', 'payment_method' => 'transfer', 'payment_bank' => $bank->name, 'items' => [['product_id' => $product->id, 'qty' => 1, 'price' => 2000]]]);
        $category = Category::create(['name' => 'HP']);
        $order = app(CreateServiceOrder::class)->create(['customer_name' => 'Ratna', 'customer_phone' => '081234567890', 'category_id' => $category->id, 'complaint' => 'Mati'], $user);
        $order->update(['status' => 'selesai', 'invoiced_on' => '2026-11-18']);
        $order->items()->create(['type' => 'jasa', 'description' => 'Pemeriksaan', 'qty' => 1, 'price' => 10000, 'cost' => 0]);
        app(ManageServiceOrder::class)->recordPayment($order->id, ['payment_method' => 'transfer', 'payment_bank' => 'Bank Jago, Syariah']);
        Livewire::test(Banks::class)->call('edit', $bank->id)->set('name', 'Bank Jago Baru')->call('save')->assertHasNoErrors();
        Livewire::test(Banks::class)->call('delete', $bank->id)->assertHasNoErrors();
        $this->assertSame('Bank Jago, Syariah', $sale->fresh()->payment_bank);
        $this->assertSame('Bank Jago, Syariah', $order->fresh()->payment_bank);
        $this->assertNotContains('Bank Jago, Syariah', array_column(Payment::bankOptions(), 'value'));
        $this->assertContains('Bank Jago, Syariah', array_column(Payment::bankOptions(all: true), 'value'));
        Livewire::test(Reports::class)->set('from', '2026-11-18')->set('to', '2026-11-18')
            ->set('payment_method', 'transfer')->set('payment_bank', 'Bank Jago, Syariah')
            ->assertViewHas('report', fn ($report) => $report !== null)
            ->assertViewHas('rows', fn ($rows) => count($rows) === 2);
        Livewire::test(Banks::class)->call('toggleDefault', 'BCA');
        $this->assertContains('BCA', array_column(Payment::bankOptions(all: true), 'value'));
    }
}
