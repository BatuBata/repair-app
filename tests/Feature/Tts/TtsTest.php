<?php

namespace Tests\Feature\Tts;

use App\Enums\ServiceOrderStatus;
use App\Livewire\Tts\Detail;
use App\Livewire\Tts\Form;
use App\Livewire\Tts\Index;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TtsTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $creator, array $extra = []): ServiceOrder
    {
        $order = app(CreateServiceOrder::class)->create([
            'customer_name' => 'Bu Ratna', 'customer_phone' => '0812-3456-7890',
            'category_id' => Category::firstOrCreate(['name' => 'Mesin cuci'])->id,
            'complaint' => 'Pengering tidak berputar',
        ], $creator);
        $order->update($extra);

        return $order;
    }

    public function test_guest_routes_require_login_and_missing_ids_are_not_found(): void
    {
        foreach (['/tts', '/tts/baru', '/tts/1', '/tts/1/edit', '/tts/1/sukses'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        foreach (['/tts/999', '/tts/999/edit', '/tts/999/sukses', '/tts/bad/edit'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_create_validates_four_required_fields_preserves_input_and_limits_optional_fields(): void
    {
        $this->actingAs(User::factory()->create());
        $component = Livewire::test(Form::class)->set('customer_name', 'Tetap di form')->call('save')
            ->assertHasErrors(['customer_phone', 'category_id', 'complaint'])->assertSet('customer_name', 'Tetap di form');
        $component->set('customer_name', str_repeat('x', 101))->set('complaint', str_repeat('x', 501))
            ->set('item_description', str_repeat('x', 101))->set('customer_address', str_repeat('x', 256))->call('save')
            ->assertHasErrors(['customer_name', 'complaint', 'item_description', 'customer_address']);
        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_create_normalizes_phone_assigns_creator_and_uses_sequence_service(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 13));
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $category = Category::create(['name' => 'TV']);
        for ($number = 1; $number <= 2; $number++) {
            Livewire::test(Form::class)->set('customer_name', 'Bu Ratna')->set('customer_phone', '0812-3456-7890')
                ->set('category_id', (string) $category->id)->set('complaint', 'Tidak menyala')
                ->set('item_description', 'Sharp')->set('customer_address', 'Jl. Uji')->call('save')->assertHasNoErrors()
                ->assertRedirect(route('tts.success', ServiceOrder::latest('id')->firstOrFail()->id));
            $this->assertDatabaseHas('service_orders', ['number' => 'TTS-2611-00'.$number, 'created_by' => $actor->id, 'customer_phone' => '6281234567890', 'status' => 'diterima', 'item_description' => 'Sharp', 'customer_address' => 'Jl. Uji']);
        }
        $order = ServiceOrder::firstOrFail();
        $this->get(route('tts.success', $order->id))->assertOk()->assertSee($order->number)->assertSee('Buat TTS lagi')->assertDontSee('Kirim WA')->assertDontSee('Cetak struk');
    }

    public function test_invalid_phone_and_inactive_or_forged_category_do_not_create_data(): void
    {
        $this->actingAs(User::factory()->create());
        $active = Category::create(['name' => 'Aktif']);
        $inactive = Category::create(['name' => 'Tidak ditawarkan', 'is_active' => false]);
        $this->get('/tts/baru')->assertSee('Aktif')->assertDontSee('Tidak ditawarkan');
        $c = Livewire::test(Form::class)->set('customer_name', 'Nama')->set('complaint', 'Keluhan')->set('customer_phone', '12345')->set('category_id', (string) $active->id)->call('save')->assertHasErrors('customer_phone');
        $c->set('customer_phone', '081234567890');
        foreach ([$inactive->id, 99999, -1, 'bad'] as $id) {
            $c->set('category_id', (string) $id)->call('save')->assertHasErrors('category_id');
        }
        $this->assertDatabaseCount('service_orders', 0);
    }

    public static function statuses(): array
    {
        return array_map(fn ($status) => [$status->value], ServiceOrderStatus::cases());
    }

    #[DataProvider('statuses')]
    public function test_technician_edit_requires_own_received_order_and_admin_can_edit_data(string $status): void
    {
        $owner = User::factory()->create();
        $order = $this->order($owner, ['status' => $status]);
        $this->actingAs(User::factory()->create())->get(route('tts.edit', $order->id))->assertForbidden();
        $this->actingAs($owner);
        if ($status === 'diterima') {
            $this->get(route('tts.edit', $order->id))->assertOk();
            Livewire::test(Form::class, ['id' => $order->id])->set('complaint', 'Diperbarui')->call('save')->assertHasNoErrors()->assertRedirect(route('tts.show', $order->id));
            $this->assertSame('Diperbarui', $order->fresh()->complaint);
        } else {
            $this->get(route('tts.edit', $order->id))->assertForbidden();
            Livewire::test(Form::class, ['id' => $order->id])->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Livewire::test(Form::class, ['id' => $order->id])->set('customer_name', 'Admin ubah')->call('save')->assertHasNoErrors();
        $this->assertSame('Admin ubah', $order->fresh()->customer_name);
        $this->assertSame($status, $order->fresh()->status->value);
    }

    public function test_open_form_rechecks_status_owner_and_current_role_before_save(): void
    {
        $actor = User::factory()->create(['role' => 'admin']);
        $peer = User::factory()->create();
        $this->actingAs($actor);
        $order = $this->order($peer);
        $component = Livewire::test(Form::class, ['id' => $order->id])->set('complaint', 'Tidak boleh');
        User::whereKey($actor->id)->update(['role' => 'teknisi']);
        $component->call('save')->assertForbidden();
        $this->assertSame('Pengering tidak berputar', $order->fresh()->complaint);
        $this->actingAs($peer);
        $component = Livewire::test(Form::class, ['id' => $order->id]);
        ServiceOrder::whereKey($order->id)->update(['status' => 'dikerjakan']);
        $component->call('save')->assertForbidden();
        $this->assertSame('Pengering tidak berputar', $order->fresh()->complaint);
    }

    public function test_save_rechecks_status_inside_transaction_before_mutation(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $order = $this->order($actor);
        $component = Livewire::test(Form::class, ['id' => $order->id])->set('complaint', 'Tertahan');
        $triggered = false;
        DB::connection()->beforeExecuting(function ($sql) use (&$triggered, $order) {
            if (! $triggered && DB::transactionLevel() > 0 && str_contains($sql, 'service_orders') && str_starts_with($sql, 'select')) {
                $triggered = true;
                ServiceOrder::whereKey($order->id)->update(['status' => 'dikerjakan']);
            }
        });
        $component->call('save')->assertForbidden();
        $this->assertTrue($triggered);
        $this->assertSame('Pengering tidak berputar', $order->fresh()->complaint);
    }

    public function test_owner_and_category_changes_after_mount_are_rechecked_without_mutating_data(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $order = $this->order($actor);
        $form = Livewire::test(Form::class, ['id' => $order->id])->set('complaint', 'Tidak boleh');
        ServiceOrder::whereKey($order->id)->update(['created_by' => User::factory()->create()->id]);
        $form->call('save')->assertForbidden();
        $this->assertSame('Pengering tidak berputar', $order->fresh()->complaint);
        $category = Category::create(['name' => 'TV']);
        $form = Livewire::test(Form::class)->set('customer_name', 'Baru')->set('customer_phone', '081234567890')->set('complaint', 'Rusak')->set('category_id', (string) $category->id);
        Category::whereKey($category->id)->update(['is_active' => false]);
        $form->call('save')->assertHasErrors('category_id');
        $this->assertDatabaseCount('service_orders', 1);
    }

    public function test_detail_identifier_cannot_be_retargeted_and_refresh_removes_revoked_edit_link(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $order = $this->order($actor);
        $detail = Livewire::test(Detail::class, ['id' => $order->id])->assertSee('Ubah TTS');
        ServiceOrder::whereKey($order->id)->update(['status' => 'dikerjakan']);
        $detail->call('$refresh')->assertDontSee('Ubah TTS');
        $this->expectException(CannotUpdateLockedPropertyException::class);
        $detail->set('orderId', 999);
    }

    public function test_list_paginates_and_resets_page_on_search(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $first = $this->order($actor);
        for ($i = 0; $i < 30; $i++) {
            $this->order($actor);
        }
        $list = Livewire::test(Index::class)->assertDontSee($first->number)->call('nextPage')->assertSee($first->number);
        $list->set('q', $first->number)->assertSee($first->number)->assertSet('paginators.page', 1);
    }

    public function test_deactivated_actor_cannot_create_or_hydrate_existing_components(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $form = Livewire::test(Form::class)->set('customer_name', 'Tidak disimpan');
        $index = Livewire::test(Index::class);
        User::whereKey($actor->id)->update(['is_active' => false]);
        $form->call('save')->assertForbidden();
        $index->call('$refresh')->assertForbidden();
        $this->assertDatabaseCount('service_orders', 0);
    }

    public function test_identifier_is_locked_against_forged_hydration(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $order = $this->order($actor);
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Form::class, ['id' => $order->id])->set('orderId', 999);
    }

    public function test_old_inactive_category_and_creator_names_are_preserved_but_new_choices_are_active_only(): void
    {
        $actor = User::factory()->create();
        $order = $this->order($actor);
        $order->category->update(['is_active' => false]);
        $actor->update(['is_active' => false]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('tts.show', $order->id))->assertSee('Mesin cuci')->assertSee($actor->name);
        Livewire::test(Form::class, ['id' => $order->id])->set('complaint', 'Tetap kategori lama')->call('save')->assertHasNoErrors();
        $this->assertSame($order->category_id, $order->fresh()->category_id);
        $this->get('/tts/baru')->assertDontSee('Mesin cuci');
    }

    public function test_list_searches_number_name_normalized_phone_and_status_without_exposing_prices(): void
    {
        $actor = User::factory()->create();
        $first = $this->order($actor);
        $second = $this->order($actor, ['customer_name' => 'Pak Hendra', 'status' => 'dikerjakan', 'customer_phone' => '6289999999999']);
        $first->items()->create(['type' => 'part', 'description' => 'RAHASIA PART', 'qty' => 1, 'price' => 919191, 'cost' => 818181]);
        $this->actingAs($actor);
        foreach ([$first->number, 'Ratna', '0812-3456-7890', '+62 812'] as $q) {
            Livewire::test(Index::class)->set('q', $q)->assertSee($first->number)->assertDontSee($second->number);
        }
        Livewire::test(Index::class)->set('status', 'dikerjakan')->assertSee($second->number)->assertDontSee($first->number);
        foreach (['/tts', route('tts.show', $first->id), route('tts.success', $first->id), route('tts.edit', $first->id)] as $path) {
            $this->get($path)->assertOk()->assertDontSee('919191')->assertDontSee('818181')->assertDontSee('RAHASIA PART')->assertDontSee('tracking_token');
        }
        $detail = Livewire::test(Detail::class, ['id' => $first->id]);
        $this->assertStringNotContainsString('818181', json_encode($detail->snapshot));
        $this->assertStringNotContainsString('919191', json_encode($detail->snapshot));
        $this->assertStringNotContainsString('items', json_encode($detail->snapshot));
    }
}
