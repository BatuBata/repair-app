<?php

namespace Tests\Feature\Domain;

use App\Enums\ServiceItemType;
use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Services\DocumentNumber;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_use_wita_month_prefix_and_minimum_three_digits(): void
    {
        $numbers = app(DocumentNumber::class);
        DB::transaction(function () use ($numbers) {
            $this->assertSame('TTS-2611-001', $numbers->next('TTS', new CarbonImmutable('2026-11-30 15:59:59 UTC')));
            $this->assertSame('TTS-2612-001', $numbers->next('TTS', new CarbonImmutable('2026-11-30 16:00:00 UTC')));
            $this->assertSame('JL-2612-001', $numbers->next('JL', new CarbonImmutable('2026-11-30 16:00:00 UTC')));
            DB::table('number_sequences')->where('prefix', 'TTS')->where('period', '2611')->update(['last_seq' => 999]);
            $this->assertSame('TTS-2611-1000', $numbers->next('TTS', new CarbonImmutable('2026-11-30 15:59:59 UTC')));
        });
    }

    public function test_counter_rolls_back_with_failed_document(): void
    {
        try {
            DB::transaction(function () {
                app(DocumentNumber::class)->next('TTS');
                throw new \RuntimeException('Synthetic failed save');
            });
        } catch (\RuntimeException) {
        }
        $this->assertDatabaseCount('number_sequences', 0);
    }

    public function test_counter_requires_transaction(): void
    {
        // RefreshDatabase has an outer transaction; remove it for this boundary test.
        DB::rollBack();
        $this->expectException(\LogicException::class);
        app(DocumentNumber::class)->next('TTS');
    }

    public function test_phone_normalization_and_invalid_values(): void
    {
        $this->assertSame('6281234567890', PhoneNumber::normalize('0812-3456-7890'));
        $this->assertSame('6281234567890', PhoneNumber::normalize('+62 812 3456 7890'));
        foreach (['12345', '', '6281234567890123', '62812abc12345', '62812.345678', '١٢٣٤٥٦٧٨٩٠', '81234567890'] as $phone) {
            try {
                PhoneNumber::normalize($phone);
                $this->fail('Invalid phone accepted');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('customer_phone', $exception->errors());
            }
        }
    }

    public function test_exact_status_graph_and_admin_authorization(): void
    {
        $edges = ['diterima' => ['dikerjakan', 'batal'], 'dikerjakan' => ['selesai', 'batal'], 'selesai' => ['diambil']];
        foreach (ServiceOrderStatus::cases() as $from) {
            foreach (ServiceOrderStatus::cases() as $to) {
                $allowed = in_array($to->value, $edges[$from->value] ?? [], true);
                $this->assertSame($allowed, $from->canTransitionTo($to));
                try {
                    $from->assertTransitionTo($to, UserRole::Admin);
                    $this->assertTrue($allowed);
                } catch (ValidationException) {
                    $this->assertFalse($allowed);
                }
            }
        }
        $this->expectException(AuthorizationException::class);
        ServiceOrderStatus::Diterima->assertTransitionTo(ServiceOrderStatus::Dikerjakan, UserRole::Teknisi);
    }

    public function test_creation_normalizes_phone_generates_unique_tokens_and_ignores_injected_fields(): void
    {
        $creator = User::factory()->create();
        $category = Category::create(['name' => 'TV']);
        $input = ['customer_name' => 'Pelanggan sintetis', 'customer_phone' => '0812-3456-7890', 'category_id' => $category->id, 'complaint' => 'Tidak menyala', 'status' => 'selesai', 'tracking_token' => 'attacker'];
        CarbonImmutable::setTestNow(new CarbonImmutable('2026-12-01 00:00:00 Asia/Makassar'));
        try {
            $tokens = [];
            for ($i = 1; $i <= 25; $i++) {
                $order = app(CreateServiceOrder::class)->create($input, $creator)->refresh();
                $tokens[] = $order->tracking_token;
                $this->assertSame('TTS-2612-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), $order->number);
                $this->assertSame('6281234567890', $order->customer_phone);
                $this->assertSame(ServiceOrderStatus::Diterima, $order->status);
                $this->assertGreaterThanOrEqual(32, strlen($order->tracking_token));
                $this->assertSame('2026-12-01', $order->created_at->format('Y-m-d'));
            }
            $this->assertCount(25, array_unique($tokens));
            $this->assertSame($category->id, $order->category->id);
            $this->assertSame($creator->id, $order->creator->id);
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_inactive_category_and_missing_required_input_are_rejected_without_counter(): void
    {
        $creator = User::factory()->create();
        $category = Category::create(['name' => 'Nonaktif', 'is_active' => false]);
        try {
            app(CreateServiceOrder::class)->create(['category_id' => $category->id], $creator);
            $this->fail('Invalid input accepted');
        } catch (ValidationException $exception) {
            foreach (['customer_name', 'customer_phone', 'category_id', 'complaint'] as $key) {
                $this->assertArrayHasKey($key, $exception->errors());
            }
        }
        $this->assertDatabaseCount('service_orders', 0);
        $this->assertDatabaseCount('number_sequences', 0);
    }

    public function test_money_uses_integers_service_cost_is_zero_and_subtotals_are_exact(): void
    {
        $order = $this->order();
        $item = $order->items()->create(['type' => ServiceItemType::Jasa, 'description' => 'Perbaikan', 'qty' => 2, 'price' => 100000, 'cost' => 5000]);
        $this->assertSame(200000, $item->subtotalPrice());
        $this->assertSame(0, $item->subtotalCost());
        $part = $order->items()->create(['type' => 'part', 'description' => 'Part', 'qty' => 3, 'price' => 12500, 'cost' => 10000]);
        $this->assertSame(37500, $part->subtotalPrice());
        $this->assertSame(30000, $part->subtotalCost());
        foreach ([['qty' => 0], ['price' => -1], ['price' => 1.5], ['cost' => 4294967296]] as $invalid) {
            try {
                $order->items()->create([...['type' => 'part', 'description' => 'Invalid', 'qty' => 1, 'price' => 0, 'cost' => 0], ...$invalid]);
                $this->fail('Invalid money or quantity accepted');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_domain_tables_indexes_foreign_keys_and_cascade(): void
    {
        foreach (['categories', 'number_sequences', 'service_orders', 'service_order_items', 'products', 'stock_entries', 'sales', 'sale_items', 'expenses', 'store_settings'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        foreach (['status', 'invoiced_on', 'warranty_until', 'customer_phone'] as $column) {
            $this->assertTrue(Schema::hasIndex('service_orders', [$column]));
        }
        foreach (['number', 'tracking_token'] as $column) {
            $this->assertTrue(Schema::hasIndex('service_orders', [$column], 'unique'));
        }
        $order = $this->order();
        $order->items()->create(['type' => 'part', 'description' => 'Part', 'qty' => 1, 'price' => 0, 'cost' => 0]);
        $order->delete();
        $this->assertDatabaseCount('service_order_items', 0);
        $this->expectException(QueryException::class);
        DB::table('stock_entries')->insert(['product_id' => 999, 'created_by' => 999, 'qty' => 1, 'cost_price' => 0, 'entry_date' => '2026-11-12']);
    }

    public function test_sale_snapshots_relations_dates_and_expenses(): void
    {
        $creator = User::factory()->create();
        $product = Product::create(['name' => 'Printer sintetis', 'cost_price' => 100, 'sell_price' => 150, 'stock' => 1]);
        $sale = Sale::create(['number' => app(DocumentNumber::class)->next('JL'), 'sold_on' => '2026-11-12', 'created_by' => $creator->id, 'customer_phone' => '081234567890']);
        $line = $sale->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'qty' => 1, 'price' => $product->sell_price, 'cost' => $product->cost_price]);
        $product->update(['cost_price' => 200]);
        $this->assertSame(100, $line->refresh()->subtotalCost());
        $this->assertSame('2026-11-12', $sale->sold_on->format('Y-m-d'));
        $this->assertSame('6281234567890', $sale->customer_phone);
        $entry = $product->stockEntries()->create(['qty' => 1, 'cost_price' => 100, 'entry_date' => '2026-11-12', 'created_by' => $creator->id]);
        $this->assertSame($creator->id, $entry->creator->id);
        Expense::create(['spent_on' => '2026-11-12', 'category' => 'bensin', 'amount' => 10000, 'created_by' => $creator->id]);
        $sale->delete();
        $this->assertDatabaseCount('sale_items', 0);
    }

    public function test_secure_idempotent_seed_preserves_existing_password_and_settings(): void
    {
        config(['repair.admin_initial_password' => 'Synthetic-test-password-123!']);
        try {
            $this->seed();
            $admin = User::where('username', 'admin')->firstOrFail();
            $hash = $admin->password;
            $this->assertTrue(Hash::check('Synthetic-test-password-123!', $hash));
            $this->assertSame(UserRole::Admin, $admin->role);
            $this->assertTrue($admin->is_active);
            $this->assertDatabaseCount('categories', 9);
            $settings = StoreSetting::findOrFail(1);
            $this->assertSame('', $settings->name);
            $this->assertSame('', $settings->address);
            $this->assertSame('', $settings->phone);
            $this->assertSame(30, $settings->default_warranty_days);
            $settings->update(['name' => 'Toko sintetis']);
            config(['repair.admin_initial_password' => null]);
            $this->seed();
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('store_settings', 1);
            $this->assertSame($hash, $admin->refresh()->password);
            $this->assertSame('Toko sintetis', $settings->refresh()->name);
        } finally {
            config(['repair.admin_initial_password' => null]);
        }
    }

    public function test_seed_fails_safely_without_initial_password(): void
    {
        config(['repair.admin_initial_password' => null]);
        try {
            $this->seed();
            $this->fail('Missing initial password accepted');
        } catch (ValidationException) {
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('categories', 0);
            $this->assertDatabaseCount('store_settings', 0);
        } finally {
            config(['repair.admin_initial_password' => null]);
        }
    }

    public function test_invalid_stock_expense_and_setting_values_are_rejected(): void
    {
        $creator = User::factory()->create();
        $cases = [
            fn () => Product::create(['name' => 'Synthetic', 'cost_price' => 0, 'sell_price' => 0, 'stock' => -1]),
            fn () => Expense::create(['spent_on' => '2026-11-12', 'category' => 'bensin', 'amount' => 0, 'created_by' => $creator->id]),
            fn () => StoreSetting::create(['id' => 2, 'address' => '', 'default_warranty_days' => 30]),
            fn () => StoreSetting::create(['address' => '', 'default_warranty_days' => -1]),
        ];
        foreach ($cases as $create) {
            try {
                $create();
                $this->fail('Invalid domain value accepted');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('store_settings', 0);
    }

    public function test_database_enforces_unique_tracking_token_and_document_number(): void
    {
        $one = $this->order();
        $two = app(CreateServiceOrder::class)->create([
            'customer_name' => 'Synthetic two', 'customer_phone' => '081234567890',
            'category_id' => $one->category_id, 'complaint' => 'Mati',
        ], $one->creator);
        foreach (['tracking_token', 'number'] as $column) {
            try {
                DB::table('service_orders')->where('id', $two->id)->update([$column => $one->$column]);
                $this->fail('Duplicate identity accepted');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
    }

    private function order(): ServiceOrder
    {
        return app(CreateServiceOrder::class)->create([
            'customer_name' => 'Sintetis', 'customer_phone' => '081234567890',
            'category_id' => Category::create(['name' => 'TV'])->id, 'complaint' => 'Mati',
        ], User::factory()->create());
    }
}
