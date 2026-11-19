<?php

namespace Tests\Feature\Expenses;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ExpenseCategoriesMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->date('spent_on');
            $table->enum('category', ['bensin', 'konsumsi', 'gaji', 'lainnya']);
            $table->string('description')->nullable();
            $table->unsignedInteger('amount');
            $table->timestamps();
        });
        DB::table('tenants')->insert(['id' => 1]);
        foreach (['bensin', 'konsumsi', 'gaji', 'lainnya'] as $name) {
            DB::table('expenses')->insert(['tenant_id' => 1, 'spent_on' => '2026-11-19', 'category' => $name, 'description' => 'Snapshot '.$name, 'amount' => 15000, 'created_at' => '2026-11-19 08:00:00', 'updated_at' => '2026-11-19 08:30:00']);
        }
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_19_010000_create_expense_categories.php');
    }

    public function test_widening_preserves_every_existing_field_and_accepts_custom_snapshots(): void
    {
        $before = DB::table('expenses')->orderBy('id')->get()->toJson();
        $this->migration()->up();
        $this->assertSame($before, DB::table('expenses')->orderBy('id')->get()->toJson());
        $this->assertSame(0, DB::table('expense_categories')->count());
        $custom = str_repeat('x', 50);
        DB::table('expenses')->insert(['tenant_id' => 1, 'spent_on' => '2026-11-19', 'category' => $custom, 'amount' => 15000]);
        $this->assertSame($custom, DB::table('expenses')->orderByDesc('id')->value('category'));
        DB::table('expense_categories')->insert(['tenant_id' => 1, 'name' => 'Listrik', 'is_active' => false]);
        $this->assertSame(1, DB::table('expense_categories')->count());
    }

    public function test_rollback_and_reapply_preserve_legacy_rows(): void
    {
        $before = DB::table('expenses')->orderBy('id')->get()->toJson();
        $migration = $this->migration();
        $migration->up();
        $migration->down();
        $this->assertFalse(Schema::hasTable('expense_categories'));
        $this->assertSame($before, DB::table('expenses')->orderBy('id')->get()->toJson());
        $migration->up();
        $this->assertSame($before, DB::table('expenses')->orderBy('id')->get()->toJson());
    }

    public function test_rollback_refuses_custom_snapshots_before_changing_schema_or_rows(): void
    {
        $migration = $this->migration();
        $migration->up();
        DB::table('expenses')->where('id', 1)->update(['category' => 'Listrik']);
        DB::table('expense_categories')->insert(['tenant_id' => 1, 'name' => 'Listrik']);
        $before = DB::table('expenses')->orderBy('id')->get()->toJson();
        try {
            $migration->down();
            $this->fail('Rollback kategori tambahan harus ditolak.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('Rollback ditolak', $exception->getMessage());
        }
        $this->assertSame($before, DB::table('expenses')->orderBy('id')->get()->toJson());
        $this->assertTrue(Schema::hasTable('expense_categories'));
        $this->assertSame(1, DB::table('expense_categories')->count());
    }
}
