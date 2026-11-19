<?php

// Run with project PHP 8.3; only the one uniquely prefixed synthetic table are mutated.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$environment = Dotenv\Dotenv::parse(file_get_contents(getenv('REPAIR_MYSQL_ENV_FILE') ?: base_path('.env')));
$prefix = 't087_a1_'.bin2hex(random_bytes(3)).'_';
$connection = [...config('database.connections.mysql'), 'url' => null, 'database' => 'repair_app_test', 'prefix' => $prefix, 'prefix_indexes' => false];
foreach (['host', 'port', 'username', 'password'] as $key) {
    $connection[$key] = $environment['DB_'.strtoupper($key)] ?? $connection[$key];
}
if (! in_array($connection['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new LogicException('STOP: only local MySQL is permitted.');
}
config(['database.default' => 't087_mysql', 'database.connections.t087_mysql' => $connection]);
DB::purge('t087_mysql');
if (DB::connection()->getDatabaseName() !== 'repair_app_test') {
    throw new LogicException('STOP: only the explicitly reserved test database is permitted.');
}
$tables = ['sales'];
foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        throw new LogicException('STOP: a generated fixture table already exists.');
    }
}
$check = function (bool $ok, string $message): void {
    if (! $ok) {
        throw new RuntimeException($message);
    }
};
$snapshot = function () use ($prefix): array {
    $result = [];
    foreach (Schema::getTables(DB::connection()->getDatabaseName()) as $table) {
        $name = $table['name'];
        if (str_starts_with($name, $prefix)) {
            continue;
        }
        $quoted = '`'.str_replace('`', '``', $name).'`';
        $rows = array_map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR), DB::select('SELECT * FROM '.$quoted));
        sort($rows);
        $result[$name] = ['count' => count($rows), 'sha256' => hash('sha256', implode("\n", $rows)), 'schema_sha256' => hash('sha256', json_encode(DB::select('SHOW CREATE TABLE '.$quoted), JSON_THROW_ON_ERROR))];
    }
    ksort($result);

    return $result;
};
$baseline = $snapshot();
$checks = [];
try {
    Schema::create('sales', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('number');
        $table->date('sold_on');
        $table->string('customer_name')->nullable();
        $table->unsignedInteger('warranty_days')->default(0);
        $table->timestamps();
    });
    foreach ([1, 2] as $tenant) {
        DB::table('sales')->insert(['tenant_id' => $tenant, 'number' => 'T087-LEGACY-'.$tenant, 'sold_on' => '2026-11-19', 'customer_name' => 'Synthetic fixture '.$tenant, 'warranty_days' => $tenant * 10, 'created_at' => '2026-11-19 08:00:00', 'updated_at' => '2026-11-19 08:30:00']);
    }
    $legacy = fn () => DB::table('sales')->orderBy('id')->get()->map(function ($row) { unset($row->discount); return $row; })->toJson();
    $before = $legacy();
    $migration = require database_path('migrations/2026_11_19_020000_add_sale_discount.php');
    $migration->up();
    $check($before === $legacy(), 'Legacy fields changed.');
    $check(DB::table('sales')->where('discount', 0)->count() === 2, 'Legacy discount default not zero.');
    $column = collect(Schema::getColumns('sales'))->firstWhere('name', 'discount');
    $check($column['type'] === 'bigint unsigned', 'Expected unsigned bigint.');
    $checks[] = 'Actual additive migration: unsigned bigint discount default0; all legacy fields unchanged across 2 tenants.';
    DB::table('sales')->where('id', 1)->update(['discount' => PHP_INT_MAX]);
    $check((string) DB::table('sales')->where('id', 1)->value('discount') === (string) PHP_INT_MAX, 'Large integer not exact.');
    $unsafeBefore = DB::table('sales')->orderBy('id')->get()->toJson();
    try {
        $migration->down();
        throw new RuntimeException('Unsafe rollback was not blocked.');
    } catch (LogicException $exception) {
        $check(str_contains($exception->getMessage(), 'Rollback ditolak'), 'Unexpected rollback exception.');
    }
    $check($unsafeBefore === DB::table('sales')->orderBy('id')->get()->toJson() && Schema::hasColumn('sales', 'discount'), 'Unsafe rollback changed data/schema.');
    $checks[] = 'PHP_INT_MAX discount exact; rollback with any discounted tenant row blocked before schema/data changes.';
    DB::table('sales')->where('id', 1)->update(['discount' => 0]);
    $migration->down();
    $check(! Schema::hasColumn('sales', 'discount') && $before === $legacy(), 'Safe rollback changed legacy fields.');
    $migration->up();
    $check($before === $legacy() && DB::table('sales')->where('discount', 0)->count() === 2, 'Reapply changed legacy fields.');
    $checks[] = 'Safe rollback and reapply preserve all original legacy fields and zero defaults.';
} finally {
    Schema::dropIfExists('sales');
}
$after = $snapshot();
$check($baseline === $after, 'Existing database baseline changed.');
$checks[] = 'All pre-existing tables, row counts, row hashes and schema hashes unchanged; only 1 owned fixture table created/dropped.';
$report = ['result' => 'PASS', 'mysql_version' => DB::selectOne('SELECT VERSION() AS version')->version, 'database' => 'repair_app_test', 'fixture_tables' => [$prefix.'sales'], 'fixture_tables_removed' => true, 'legacy_fixture_rows' => 2, 'discount_column' => $column, 'baseline_tables' => $baseline, 'checks' => $checks];
file_put_contents(__DIR__.'/mysql-proof.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode(['result' => 'PASS', 'checks' => count($checks), 'baseline_tables' => count($baseline), 'fixture_tables' => $report['fixture_tables']], JSON_THROW_ON_ERROR)."\n";
