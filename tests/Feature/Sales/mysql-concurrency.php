<?php

use App\Actions\Sales\CreateSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$root = getenv('QA_RUNTIME');
if (! $root || ! is_dir($root) || ! str_starts_with(realpath($root), '/private/tmp/t013-mysql-')) {
    throw new RuntimeException('Private task runtime required.');
}
$source = Dotenv\Dotenv::parse(file_get_contents('/Users/muzib/Projects/web/repair-app/.env'));
config(['database.default' => 'mysql', 'database.connections.mysql.url' => null, 'database.connections.mysql.host' => $source['DB_HOST'] ?? '127.0.0.1', 'database.connections.mysql.port' => $source['DB_PORT'] ?? '3306', 'database.connections.mysql.database' => 'repair_app_test', 'database.connections.mysql.username' => $source['DB_USERNAME'] ?? 'root', 'database.connections.mysql.password' => $source['DB_PASSWORD'] ?? '', 'database.connections.mysql.unix_socket' => $source['DB_SOCKET'] ?? '']);
unset($source);
DB::purge('mysql');
$mode = $argv[1] ?? '';
$save = function (string $name, mixed $data) use ($root): void {
    file_put_contents($root.'/'.$name, serialize($data));
    chmod($root.'/'.$name, 0600);
};
$load = fn (string $name) => unserialize(file_get_contents($root.'/'.$name), ['allowed_classes' => false]);
$rows = function (): array {
    $result = [];
    foreach (DB::select('SHOW TABLES') as $table) {
        $name = array_values((array) $table)[0];
        $result[$name] = DB::table($name)->get()->map(fn ($row) => (array) $row)->all();
    }
    ksort($result);

    return $result;
};
if ($mode === 'inventory') {
    $tables = $rows();
    foreach (['users', 'products', 'sales', 'sale_items', 'number_sequences'] as $required) {
        if (! array_key_exists($required, $tables)) {
            throw new RuntimeException('Required table missing; no mutations permitted.');
        }
        $status = DB::selectOne('SHOW TABLE STATUS WHERE Name = ?', [$required]);
        if ($status->Engine !== 'InnoDB') {
            throw new RuntimeException('Required table is not InnoDB; no mutations permitted.');
        }
    }
    $columns = DB::select('SHOW COLUMNS FROM products');
    $stock = collect($columns)->firstWhere('Field', 'stock');
    if (! $stock || $stock->Type !== 'int unsigned') {
        throw new RuntimeException('Unexpected stock schema; no mutations permitted.');
    }
    foreach (['users' => ['id', 'username', 'role', 'is_active'], 'products' => ['id', 'name', 'cost_price', 'sell_price', 'stock', 'is_active'], 'sales' => ['id', 'number', 'sold_on', 'created_by', 'warranty_days', 'warranty_until'], 'sale_items' => ['id', 'sale_id', 'product_id', 'product_name', 'qty', 'price', 'cost'], 'number_sequences' => ['prefix', 'period', 'last_seq']] as $table => $requiredColumns) {
        if (array_diff($requiredColumns, DB::getSchemaBuilder()->getColumnListing($table))) {
            throw new RuntimeException('Required columns missing; no mutations permitted.');
        }
    }
    $numberIndex = collect(DB::getSchemaBuilder()->getIndexes('sales'))->first(fn ($index) => $index['unique'] && $index['columns'] === ['number']);
    if (! $numberIndex) {
        throw new RuntimeException('Unique document number index missing; no mutations permitted.');
    }
    $save('baseline', $tables);
    echo json_encode(['mode' => 'inventory', 'database' => 'repair_app_test', 'tables' => count($tables), 'counts' => array_map('count', $tables), 'required_engines' => 'InnoDB', 'stock_type' => 'int unsigned', 'baseline_saved_privately' => true])."\n";
} elseif ($mode === 'init') {
    if ($load('baseline') !== $rows()) {
        throw new RuntimeException('Baseline changed after inventory; no fixture appended.');
    }
    $prefix = 't013a1_'.bin2hex(random_bytes(5));
    $owned = DB::transaction(function () use ($prefix) {
        $admins = [];
        foreach (['a', 'b'] as $suffix) {
            $admins[] = User::factory()->create(['username' => $prefix.'_'.$suffix, 'name' => $prefix.' '.$suffix, 'role' => 'admin', 'password' => bin2hex(random_bytes(24))])->id;
        }
        $product = Product::create(['name' => $prefix.' stock race', 'cost_price' => 25000, 'sell_price' => 40000, 'stock' => 1]);

        return ['prefix' => $prefix, 'admins' => $admins, 'product' => $product->id, 'period' => now('Asia/Makassar')->format('ym')];
    });
    $save('owned', $owned);
    echo json_encode(['mode' => 'init', 'appended_users' => 2, 'appended_products' => 1, 'preexisting_rows_untouched' => true])."\n";
} elseif ($mode === 'worker') {
    $owned = $load('owned');
    $worker = (int) $argv[2];
    auth()->login(User::findOrFail($owned['admins'][$worker]));
    Product::updating(function (Product $product) use ($owned) {
        if ($product->id === $owned['product']) {
            usleep(250000);
        }
    });
    $lockDurations = [];
    DB::listen(function ($query) use (&$lockDurations) {
        if (str_contains($query->sql, '`products`') && str_contains($query->sql, 'for update')) {
            $lockDurations[] = $query->time;
        }
    });
    touch($root.'/ready-'.$worker);
    $deadline = microtime(true) + 10;
    while (! file_exists($root.'/go')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Finite race barrier timed out.');
        }
        usleep(10000);
    }
    $start = microtime(true);
    try {
        $sale = app(CreateSale::class)->handle(['sold_on' => now('Asia/Makassar')->toDateString(), 'customer_name' => $owned['prefix'], 'customer_phone' => '', 'warranty_days' => '0', 'items' => [['product_id' => (string) $owned['product'], 'qty' => '1', 'price' => '40000']]]);
        $result = ['result' => 'success', 'sale' => $sale->id];
    } catch (ValidationException $e) {
        $result = ['result' => 'shortage', 'qty_error' => array_key_exists('items.0.qty', $e->errors())];
    }
    $result += ['worker' => $worker, 'start' => $start, 'elapsed_ms' => round((microtime(true) - $start) * 1000, 1), 'product_lock_ms' => $lockDurations];
    $save('worker-'.$worker, $result);
    echo json_encode(array_diff_key($result, ['sale' => true]))."\n";
} elseif ($mode === 'proof') {
    $baseline = $load('baseline');
    $owned = $load('owned');
    $after = $rows();
    $results = [$load('worker-0'), $load('worker-1')];
    $outcomes = array_column($results, 'result');
    sort($outcomes);
    $sales = Sale::whereIn('created_by', $owned['admins'])->with('items')->get();
    $delta = [];
    foreach ($after as $table => $current) {
        $delta[$table] = count($current) - count($baseline[$table]);
        $counts = array_count_values(array_map('serialize', $current));
        foreach ($baseline[$table] as $row) {
            if ($table === 'number_sequences' && $row['prefix'] === 'JL' && $row['period'] === $owned['period']) {
                $row['last_seq']++;
            }
            $key = serialize($row);
            if (($counts[$key] ?? 0) < 1) {
                throw new RuntimeException('Preexisting row was altered unexpectedly: '.$table);
            }
            $counts[$key]--;
        }
    }
    if ($outcomes !== ['shortage', 'success'] || Product::findOrFail($owned['product'])->stock !== 0 || $sales->count() !== 1 || $sales->first()->items->count() !== 1 || ! $results[array_search('shortage', array_column($results, 'result'))]['qty_error']) {
        throw new RuntimeException('Concurrent create acceptance failed.');
    }
    $expected = ['users' => 2, 'products' => 1, 'sales' => 1, 'sale_items' => 1];
    foreach ($delta as $table => $count) {
        if ($table !== 'number_sequences' && $count !== ($expected[$table] ?? 0)) {
            throw new RuntimeException('Unexpected table count delta: '.$table);
        }
    }
    $sequenceBefore = collect($baseline['number_sequences'])->first(fn ($r) => $r['prefix'] === 'JL' && $r['period'] === $owned['period']);
    $sequence = DB::table('number_sequences')->where('prefix', 'JL')->where('period', $owned['period'])->value('last_seq');
    if ((int) $sequence !== ($sequenceBefore['last_seq'] ?? 0) + 1 || $delta['number_sequences'] !== ($sequenceBefore ? 0 : 1)) {
        throw new RuntimeException('Document sequence did not increment exactly once.');
    }
    echo json_encode(['verdict' => 'pass', 'distinct_active_admins' => true, 'actual_action' => CreateSale::class, 'outcomes' => $outcomes, 'stock' => 0, 'new_sales' => 1, 'new_items' => 1, 'orphan_items' => DB::table('sale_items')->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')->whereNull('sales.id')->count(), 'sequence_increment' => 1, 'unique_new_number' => true, 'preexisting_baseline_rows_preserved' => true, 'owned_new_fixture_rows_retained' => true, 'table_count_deltas' => $delta, 'worker_timing' => array_map(fn ($r) => array_diff_key($r, ['sale' => true]), $results)])."\n";
} else {
    throw new RuntimeException('Unknown mode.');
}
