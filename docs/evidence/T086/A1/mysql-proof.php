<?php

// Run with project PHP 8.3; only the three uniquely prefixed synthetic tables are mutated.
require dirname(__DIR__, 4).'/vendor/autoload.php';
$app = require dirname(__DIR__, 4).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$environment = Dotenv\Dotenv::parse(file_get_contents(getenv('REPAIR_MYSQL_ENV_FILE') ?: base_path('.env')));
$prefix = 't086_a1_'.bin2hex(random_bytes(3)).'_';
$connection = [...config('database.connections.mysql'), 'url' => null, 'database' => 'repair_app_test', 'prefix' => $prefix, 'prefix_indexes' => false];
foreach (['host', 'port', 'username', 'password'] as $key) {
    $connection[$key] = $environment['DB_'.strtoupper($key)] ?? $connection[$key];
}
if (! in_array($connection['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new LogicException('STOP: only local MySQL is permitted.');
}
config(['database.default' => 't086_mysql', 'database.connections.t086_mysql' => $connection]);
DB::purge('t086_mysql');
if (DB::connection()->getDatabaseName() !== 'repair_app_test') {
    throw new LogicException('STOP: only the explicitly reserved test database is permitted.');
}
$tables = ['tenants', 'expenses', 'expense_categories'];
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
    Schema::create('tenants', fn (Blueprint $table) => $table->id());
    Schema::create('expenses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
        $table->date('spent_on');
        $table->enum('category', ['bensin', 'konsumsi', 'gaji', 'lainnya']);
        $table->string('description')->nullable();
        $table->unsignedInteger('amount');
        $table->unsignedBigInteger('created_by');
        $table->timestamps();
        $table->index('spent_on');
    });
    DB::table('tenants')->insert(['id' => 1]);
    foreach (['bensin', 'konsumsi', 'gaji', 'lainnya'] as $i => $category) {
        DB::table('expenses')->insert(['tenant_id' => 1, 'spent_on' => '2026-11-19', 'category' => $category, 'description' => 'Synthetic T086 '.$category, 'amount' => $i + 1, 'created_by' => 1, 'created_at' => '2026-11-19 08:00:00', 'updated_at' => '2026-11-19 08:30:00']);
    }
    $before = DB::table('expenses')->orderBy('id')->get()->toJson();
    $migration = require database_path('migrations/2026_11_19_010000_create_expense_categories.php');
    $migration->up();
    $check($before === DB::table('expenses')->orderBy('id')->get()->toJson(), 'Legacy row changed.');
    $categoryColumn = collect(Schema::getColumns('expenses'))->firstWhere('name', 'category');
    $check($categoryColumn['type'] === 'varchar(50)', 'Expected varchar(50).');
    $checks[] = 'Actual migration widened ENUM to varchar(50); all 4 legacy rows byte-identical.';
    $check(DB::table('expense_categories')->count() === 0, 'Unexpected historical backfill.');
    DB::table('expense_categories')->insert(['tenant_id' => 1, 'name' => 'Listrik', 'is_active' => false]);
    $longName = str_repeat('x', 50);
    $id = DB::table('expenses')->insertGetId(['tenant_id' => 1, 'spent_on' => '2026-11-19', 'category' => $longName, 'amount' => 1, 'created_by' => 1]);
    $check(DB::table('expenses')->where('id', $id)->value('category') === $longName, 'Custom snapshot was truncated.');
    $checks[] = 'New tenant category configuration and 50-character custom snapshot accepted; no legacy backfill.';
    $unsafeBefore = DB::table('expenses')->orderBy('id')->get()->toJson();
    try {
        $migration->down();
        throw new RuntimeException('Unsafe rollback was not blocked.');
    } catch (LogicException $exception) {
        $check(str_contains($exception->getMessage(), 'Rollback ditolak'), 'Unexpected rollback exception.');
    }
    $check($unsafeBefore === DB::table('expenses')->orderBy('id')->get()->toJson() && Schema::hasTable('expense_categories'), 'Unsafe rollback changed data/schema.');
    $checks[] = 'Unsafe narrowing rollback blocked before changing expense rows or category table.';
    DB::table('expenses')->where('id', $id)->delete();
    $migration->down();
    $check($before === DB::table('expenses')->orderBy('id')->get()->toJson(), 'Safe rollback changed legacy data.');
    $check(! Schema::hasTable('expense_categories'), 'Rollback left master table.');
    $migration->up();
    $check($before === DB::table('expenses')->orderBy('id')->get()->toJson(), 'Reapply changed legacy data.');
    $checks[] = 'Safe rollback and reapply preserve every legacy field.';
} finally {
    foreach (array_reverse($tables) as $table) {
        Schema::dropIfExists($table);
    }
}
$after = $snapshot();
$check($baseline === $after, 'Existing database baseline changed.');
$checks[] = 'All pre-existing tables, row counts, row hashes and schema hashes unchanged; only 3 owned fixture tables created/dropped.';
$report = ['result' => 'PASS', 'mysql_version' => DB::selectOne('SELECT VERSION() AS version')->version, 'database' => 'repair_app_test', 'fixture_tables' => array_map(fn ($table) => $prefix.$table, $tables), 'fixture_tables_removed' => true, 'legacy_fixture_rows' => 4, 'baseline_tables' => $baseline, 'checks' => $checks];
file_put_contents(__DIR__.'/mysql-proof.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode(['result' => 'PASS', 'checks' => count($checks), 'baseline_tables' => count($baseline), 'fixture_tables' => $report['fixture_tables']], JSON_THROW_ON_ERROR)."\n";
