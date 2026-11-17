<?php

namespace Tests\Feature\Tenancy;

use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Always a new in-memory connection; never migrate the operator's database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach (glob(database_path('migrations/*.php')) as $file) {
            if (! str_contains($file, 'add_tenant_foundation')) {
                Artisan::call('migrate', ['--path' => $file, '--realpath' => true, '--force' => true]);
            }
        }
        self::seedLegacy();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public static function seedLegacy(): void
    {
        $stamp = ['created_at' => '2026-10-31 08:00:00', 'updated_at' => '2026-10-31 09:00:00'];
        foreach ([1 => 'admin', 2 => 'teknisi'] as $id => $role) {
            DB::table('users')->insert(['id' => $id, 'name' => 'Fixture '.$role, 'username' => 's008.'.$role, 'password' => Hash::make('synthetic-s008'), 'role' => $role, 'is_active' => true, ...$stamp]);
        }
        DB::table('categories')->insert(['id' => 1, 'name' => 'Elektronik sintetis', 'is_active' => true, ...$stamp]);
        DB::table('products')->insert(['id' => 1, 'name' => 'Produk sintetis', 'cost_price' => 200, 'sell_price' => 500, 'stock' => 7, 'is_active' => true, ...$stamp]);
        DB::table('store_settings')->insert(['id' => 1, 'name' => 'Anugerah', 'address' => 'Alamat fixture S008', 'phone' => '6281234567890', 'logo_path' => 'logos/legacy-s008.png', 'warranty_terms' => 'Garansi sintetis', 'default_warranty_days' => 30]);
        DB::table('service_orders')->insert(['id' => 1, 'number' => 'TTS-2610-008', 'customer_name' => 'Customer sintetis', 'customer_phone' => '6281234567890', 'customer_address' => 'Alamat sintetis', 'category_id' => 1, 'item_description' => 'Unit fixture', 'complaint' => 'Keluhan fixture', 'status' => 'diambil', 'created_by' => 1, 'technician_id' => 2, 'assigned_at' => '2026-10-31 08:30:00', 'invoiced_on' => '2026-10-31', 'warranty_days' => 30, 'warranty_until' => '2026-11-30', 'unrepairable' => false, 'picked_up_on' => '2026-10-31', 'warranty_contacted_at' => '2026-11-01 08:00:00', 'tracking_token' => str_repeat('a', 64), ...$stamp]);
        DB::table('service_orders')->insert(['id' => 2, 'number' => 'TTS-2610-009', 'customer_name' => 'Customer batal sintetis', 'customer_phone' => '6281234567891', 'category_id' => 1, 'complaint' => 'Batal fixture', 'status' => 'batal', 'created_by' => 1, 'unrepairable' => true, 'cancelled_at' => '2026-10-31 09:00:00', 'cancel_reason' => 'Alasan sintetis', 'tracking_token' => str_repeat('b', 64), ...$stamp]);
        DB::table('service_order_items')->insert(['service_order_id' => 1, 'type' => 'part', 'description' => 'Part fixture', 'qty' => 2, 'price' => 500, 'cost' => 200, ...$stamp]);
        DB::table('service_order_items')->insert(['service_order_id' => 1, 'type' => 'jasa', 'description' => 'Jasa fixture', 'qty' => 1, 'price' => 250, 'cost' => 0, ...$stamp]);
        DB::table('sales')->insert(['id' => 1, 'number' => 'JL-2610-004', 'sold_on' => '2026-10-31', 'customer_name' => 'Pembeli sintetis', 'customer_phone' => '6281234567890', 'warranty_days' => 7, 'warranty_until' => '2026-11-07', 'warranty_contacted_at' => '2026-11-08 08:00:00', 'created_by' => 1, ...$stamp]);
        DB::table('sale_items')->insert(['sale_id' => 1, 'product_id' => 1, 'product_name' => 'Produk sintetis', 'qty' => 1, 'price' => 500, 'cost' => 200, ...$stamp]);
        DB::table('stock_entries')->insert(['product_id' => 1, 'qty' => 8, 'cost_price' => 200, 'entry_date' => '2026-10-31', 'note' => 'Saldo fixture', 'created_by' => 1, ...$stamp]);
        DB::table('expenses')->insert(['spent_on' => '2026-10-31', 'category' => 'bensin', 'description' => 'Biaya fixture', 'amount' => 50, 'created_by' => 1, ...$stamp]);
        foreach (['TTS' => 9, 'JL' => 4] as $prefix => $number) {
            DB::table('number_sequences')->insert(['prefix' => $prefix, 'period' => '2610', 'last_seq' => $number]);
        }
    }

    public static function legacySnapshot(): array
    {
        $snapshot = [];
        foreach (['users', 'categories', 'products', 'store_settings', 'service_orders', 'service_order_items', 'sales', 'sale_items', 'stock_entries', 'expenses', 'number_sequences'] as $table) {
            $rows = DB::table($table)->get()->map(function ($row) {
                $row = (array) $row;
                unset($row['tenant_id']);
                ksort($row);

                return json_encode($row, JSON_THROW_ON_ERROR);
            })->all();
            sort($rows);
            $snapshot[$table] = ['rows' => count($rows), 'sha256' => hash('sha256', implode("\n", $rows))];
        }

        return $snapshot;
    }

    public function test_legacy_business_fields_and_app_reads_survive_migration(): void
    {
        $before = self::legacySnapshot();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertSame($before, self::legacySnapshot());
        foreach (['users', 'categories', 'products', 'store_settings', 'service_orders', 'sales', 'expenses', 'number_sequences'] as $table) {
            $this->assertSame(0, DB::table($table)->whereNull('tenant_id')->orWhere('tenant_id', '!=', 1)->count());
        }
        $this->assertSame('Anugerah', Tenant::findOrFail(1)->name);
        app(TenantContext::class)->set(Tenant::findOrFail(1));
        $this->assertSame('logos/legacy-s008.png', StoreSetting::firstOrFail()->logo_path);
        $this->assertSame(2, ServiceOrder::findOrFail(1)->items()->count());
        $this->assertSame('s008.teknisi', ServiceOrder::findOrFail(1)->technician->username);
        $this->actingAs(User::findOrFail(1));
        $report = app(Report::class)->build('2026-10-31', '2026-10-31');
        $this->assertSame(['1250', '500', '1750', '600', '1150', '50', '1100', '1600'], array_map(fn ($key) => $report[$key], ['service', 'sales', 'revenue', 'cost', 'gross', 'expenses', 'net', 'restock']));
        $this->assertSame($before, self::legacySnapshot());
    }

    public function test_single_tenant_down_and_reapply_preserve_every_old_field(): void
    {
        $before = self::legacySnapshot();
        $migration = require database_path('migrations/2026_11_17_000000_add_tenant_foundation.php');
        $migration->up();
        $migration->down();
        $this->assertFalse(Schema::hasTable('tenants'));
        $this->assertSame($before, self::legacySnapshot());
        $migration->up();
        $this->assertSame($before, self::legacySnapshot());
    }

    public function test_second_tenant_blocks_down_before_any_schema_or_data_change(): void
    {
        $migration = require database_path('migrations/2026_11_17_000000_add_tenant_foundation.php');
        $migration->up();
        $second = DB::table('tenants')->insertGetId(['name' => 'Tenant kedua sintetis', 'is_active' => true]);
        $before = self::legacySnapshot();
        $schema = Schema::getColumns('users');
        try {
            $migration->down();
            $this->fail('Rollback multi-tenant harus ditolak.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('tenant lain', $exception->getMessage());
        }
        $this->assertSame($before, self::legacySnapshot());
        $this->assertSame($schema, Schema::getColumns('users'));
        $this->assertDatabaseHas('tenants', ['id' => $second]);
    }
}
