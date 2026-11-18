<?php

namespace Tests\Feature\Customers;

use App\Livewire\Customers\Index;
use App\Livewire\Tts\Form;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_manages_customers_and_phone_is_unique_within_the_store(): void
    {
        $this->get('/pelanggan')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(Index::class)->call('create')->call('save')->assertHasErrors(['name', 'customer_phone'])
            ->set('name', 'Ratna')->set('customer_phone', '0812 3456 7890')->set('address', 'Samarinda')->call('save')->assertHasNoErrors();
        $customer = Customer::firstOrFail();
        $this->assertSame('6281234567890', $customer->phone);
        Livewire::test(Index::class)->call('create')->set('name', 'Duplikat')->set('customer_phone', '+62 81234567890')
            ->call('save')->assertHasErrors('customer_phone');
        Livewire::test(Index::class)->call('edit', $customer->id)->set('name', 'Ibu Ratna')->call('save')->assertHasNoErrors();
        $this->assertSame('Ibu Ratna', $customer->fresh()->name);
        Livewire::test(Index::class)->set('search', 'tidak cocok')->assertDontSee('Ibu Ratna');
        $this->actingAs(User::factory()->create(['role' => 'teknisi']))->get('/pelanggan')->assertForbidden();
        Livewire::test(Index::class)->assertForbidden();
    }

    public function test_new_tts_registers_customer_once_and_existing_choice_copies_current_data(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $category = Category::create(['name' => 'HP']);
        for ($i = 0; $i < 2; $i++) {
            Livewire::test(Form::class)->set('customer_name', 'Ratna')->set('customer_phone', '081234567890')
                ->set('customer_address', 'Alamat awal')->set('category_id', (string) $category->id)->set('complaint', 'Mati')
                ->call('save')->assertHasNoErrors();
        }
        $this->assertDatabaseCount('customers', 1);
        $customer = Customer::firstOrFail();
        Livewire::test(Form::class)->set('customer_id', (string) $customer->id)
            ->assertSet('customer_name', 'Ratna')->assertSet('customer_address', 'Alamat awal')
            ->set('customer_name', 'Dipalsukan')->set('category_id', (string) $category->id)->set('complaint', 'Layar')
            ->call('save')->assertHasNoErrors();
        $this->assertSame('Ratna', ServiceOrder::latest('id')->firstOrFail()->customer_name);
        $customer->update(['name' => 'Nama baru', 'address' => 'Alamat baru']);
        $this->assertSame('Ratna', ServiceOrder::latest('id')->firstOrFail()->customer_name);
        $this->assertSame('Alamat awal', ServiceOrder::latest('id')->firstOrFail()->customer_address);
        Livewire::test(Form::class)->set('customer_id', (string) $customer->id)->set('customer_id', '')
            ->assertSet('customer_name', '')->assertSet('customer_phone', '')->assertSet('customer_address', '');
    }

    public function test_legacy_order_edit_does_not_create_customer_and_failed_tts_has_no_side_effects(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        $category = Category::create(['name' => 'TV']);
        $order = app(CreateServiceOrder::class)->create([
            'customer_name' => 'Data lama', 'customer_phone' => '081234567890', 'category_id' => $category->id, 'complaint' => 'Mati',
        ], $user);
        $this->assertDatabaseCount('customers', 0);
        Livewire::test(Form::class, ['id' => $order->id])->set('customer_name', 'Lama diperbarui')->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('customers', 0);
        Livewire::test(Form::class)->set('customer_name', 'Gagal')->set('customer_phone', '081234567891')->call('save')->assertHasErrors();
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('service_orders', 1);
    }

    public function test_other_tenant_customers_cannot_be_seen_selected_or_edited(): void
    {
        $other = Tenant::create(['name' => 'Toko lain', 'slug' => 'toko-lain']);
        app(TenantContext::class)->set($other);
        $foreign = Customer::create(['name' => 'Rahasia toko lain', 'phone' => '081234567890']);
        $this->actingAs(User::factory()->create(['role' => 'admin', 'tenant_id' => 1]));
        app(TenantContext::class)->set(Tenant::findOrFail(1));
        Livewire::test(Index::class)->assertDontSee('Rahasia toko lain')->call('edit', $foreign->id)->assertNotFound();
        Livewire::test(Form::class)->assertDontSee('Rahasia toko lain')->set('customer_id', (string) $foreign->id)->assertNotFound();
        Livewire::test(Index::class)->call('create')->set('name', 'Lokal')->set('customer_phone', '081234567890')->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('customers', 2);
    }
}
