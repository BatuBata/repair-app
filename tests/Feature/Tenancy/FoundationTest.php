<?php

namespace Tests\Feature\Tenancy;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_has_only_the_required_tenant_fields_and_scoped_keys(): void
    {
        $this->assertSame(
            ['id', 'name', 'is_active', 'created_at', 'updated_at'],
            Schema::getColumnListing('tenants'),
        );

        foreach (['users', 'categories', 'number_sequences', 'service_orders', 'products', 'sales', 'expenses', 'store_settings'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'tenant_id'), "{$table}.tenant_id tidak ditemukan.");
            $tenantColumn = collect(Schema::getColumns($table))->firstWhere('name', 'tenant_id');
            $this->assertFalse($tenantColumn['nullable'], "{$table}.tenant_id masih nullable.");
            $this->assertTrue(collect(Schema::getForeignKeys($table))->contains(
                fn (array $foreign) => $foreign['columns'] === ['tenant_id'] && $foreign['foreign_table'] === 'tenants',
            ), "{$table}.tenant_id tidak memiliki foreign key tenants.");
        }

        $this->assertTrue(Schema::hasIndex('categories', ['tenant_id', 'name'], 'unique'));
        $this->assertTrue(Schema::hasIndex('service_orders', ['tenant_id', 'number'], 'unique'));
        $this->assertTrue(Schema::hasIndex('sales', ['tenant_id', 'number'], 'unique'));
        $this->assertTrue(Schema::hasIndex('store_settings', ['tenant_id'], 'unique'));
        $this->assertTrue(Schema::hasIndex('number_sequences', ['tenant_id', 'prefix', 'period'], 'primary'));
    }

    public function test_scope_and_create_use_context_and_ignore_forged_tenant_id(): void
    {
        $first = Tenant::findOrFail(1);
        $second = Tenant::create(['name' => 'Sumber HP']);
        $context = app(TenantContext::class);

        $context->set($first);
        $firstCategory = TenantCategory::create(['name' => 'HP', 'tenant_id' => $second->id]);
        $this->assertSame($first->id, $firstCategory->tenant_id);

        $context->set($second);
        $secondCategory = TenantCategory::create(['name' => 'HP']);
        $this->assertSame($second->id, $secondCategory->tenant_id);
        $this->assertSame([$secondCategory->id], TenantCategory::pluck('id')->all());

        $context->forget();
        $this->assertSame(0, TenantCategory::count());
        $this->expectException(LogicException::class);
        TenantCategory::create(['name' => 'Tanpa tenant']);
    }

    public function test_other_tenant_record_is_not_found_during_authenticated_request(): void
    {
        $first = Tenant::findOrFail(1);
        $second = Tenant::create(['name' => 'Sumber HP']);
        $user = User::factory()->create(['tenant_id' => $first->id]);
        $foreignId = DB::table('categories')->insertGetId([
            'tenant_id' => $second->id,
            'name' => 'HP',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Route::middleware('web')->get('/_test/tenant-category/{id}', fn (int $id) => TenantCategory::findOrFail($id)->name);
        Route::middleware('web')->get('/_test/tenant-context', fn () => (string) app(TenantContext::class)->id());

        $this->actingAs($user)->get('/_test/tenant-context')->assertOk()->assertSee((string) $first->id);
        $this->actingAs($user)->get("/_test/tenant-category/{$foreignId}")->assertNotFound();
    }

    public function test_inactive_tenant_blocks_internal_request_and_logs_user_out(): void
    {
        $tenant = Tenant::create(['name' => 'Toko Nonaktif', 'is_active' => false]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Route::middleware('web')->get('/_test/tenant-active', fn () => 'ok');

        $this->actingAs($user)->get('/_test/tenant-active')->assertForbidden();
        $this->assertGuest();
    }
}

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 */
#[Fillable(['name', 'tenant_id'])]
class TenantCategory extends Model
{
    use BelongsToTenant;

    protected $table = 'categories';
}
