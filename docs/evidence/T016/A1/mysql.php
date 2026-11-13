<?php

use App\Actions\Sales\CreateSale;
use App\Actions\Sales\DeleteSale;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$root = getenv('QA_RUNTIME');
if (! $root || ! str_starts_with($root, '/private/tmp/t016-mysql-')) {
    throw new RuntimeException('Task private runtime required');
}
$source = Dotenv\Dotenv::parse(file_get_contents(getcwd().'/.env'));
config(['database.default' => 'mysql', 'database.connections.mysql.url' => null, 'database.connections.mysql.host' => $source['DB_HOST'] ?? '127.0.0.1', 'database.connections.mysql.port' => $source['DB_PORT'] ?? '3306', 'database.connections.mysql.database' => 'repair_app_test', 'database.connections.mysql.username' => $source['DB_USERNAME'] ?? 'root', 'database.connections.mysql.password' => $source['DB_PASSWORD'] ?? '', 'database.connections.mysql.unix_socket' => $source['DB_SOCKET'] ?? '']);
unset($source);
DB::purge('mysql');
$save = function ($name, $data) use ($root) {
    file_put_contents($root.'/'.$name, serialize($data));
    chmod($root.'/'.$name, 0600);
};
$load = fn ($name) => unserialize(file_get_contents($root.'/'.$name), ['allowed_classes' => false]);
$rows = function () {
    $result = [];
    foreach (DB::select('SHOW TABLES') as $t) {
        $name = array_values((array) $t)[0];
        $result[$name] = DB::table($name)->get()->map(fn ($r) => (array) $r)->all();
    }ksort($result);

    return $result;
};
$check = function ($ok, $message) {
    if (! $ok) {
        throw new RuntimeException($message);
    }
};
$mode = $argv[1];
if ($mode === 'inventory') {
    $baseline = $rows();
    foreach (['service_orders', 'number_sequences', 'products', 'sales', 'sale_items'] as $t) {
        $check(DB::selectOne('SHOW TABLE STATUS WHERE Name = ?', [$t])->Engine === 'InnoDB', 'InnoDB required');
    }
    $check(! DB::table('number_sequences')->where('period', '3101')->exists(), 'Future period already occupied');
    $save('baseline', $baseline);
    echo json_encode(['tables' => count($baseline), 'counts' => array_map('count', $baseline), 'future_period' => '3101', 'future_period_absent' => true, 'baseline_private' => true]);
} elseif ($mode === 'init') {
    $check($load('baseline') === $rows(), 'Baseline changed before init');
    $prefix = 't016a1_'.bin2hex(random_bytes(5));
    $own = DB::transaction(function () use ($prefix) {
        $admins = [];
        foreach (['a', 'b'] as $s) {
            $admins[] = User::factory()->create(['username' => $prefix.$s, 'name' => $prefix.$s, 'role' => 'admin', 'password' => bin2hex(random_bytes(24))])->id;
        }$tech = User::factory()->create(['username' => $prefix.'t', 'name' => $prefix.'t', 'role' => 'teknisi', 'password' => bin2hex(random_bytes(24))]);
        $cat = Category::create(['name' => $prefix]);
        $prod = Product::create(['name' => $prefix, 'cost_price' => 25000, 'sell_price' => 40000, 'stock' => 1]);

        return ['admins' => $admins, 'tech' => $tech->id, 'category' => $cat->id, 'product' => $prod->id, 'prefix' => $prefix];
    });
    $save('own', $own);
    echo json_encode(['own_new_users' => 3, 'own_new_category' => 1, 'own_new_product' => 1]);
} elseif ($mode === 'tts' || $mode === 'sale') {
    $own = $load('own');
    $i = (int) $argv[2];
    auth()->login(User::findOrFail($own['admins'][$i]));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2031-01-01 00:05:00', 'Asia/Makassar'));
    Carbon::setTestNow(CarbonImmutable::now('Asia/Makassar'));
    if ($mode === 'sale') {
        Product::updating(function ($p) use ($own) {
            if ($p->id === $own['product']) {
                usleep(250000);
            }
        });
    }
    touch($root.'/'.$mode.'-ready-'.$i);
    $deadline = microtime(true) + 15;
    while (! file_exists($root.'/'.$mode.'-go')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Barrier timeout');
        }usleep(5000);
    }
    $start = microtime(true);
    try {
        if ($mode === 'tts') {
            $o = app(CreateServiceOrder::class)->create(['customer_name' => $own['prefix'], 'customer_phone' => '081234567890', 'category_id' => $own['category'], 'complaint' => 'Synthetic race'], User::findOrFail($own['tech']));
            $r = ['result' => 'success', 'id' => $o->id, 'number' => $o->number];
        } else {
            $s = app(CreateSale::class)->handle(['sold_on' => '2031-01-01', 'warranty_days' => 0, 'items' => [['product_id' => $own['product'], 'qty' => 1, 'price' => 40000]]]);
            $r = ['result' => 'success', 'id' => $s->id, 'number' => $s->number];
        }
    } catch (ValidationException$e) {
        $r = ['result' => 'shortage', 'qty_error' => isset($e->errors()['items.0.qty'])];
    }
    $r += ['start' => $start, 'duration_ms' => round((microtime(true) - $start) * 1000, 2)];
    $save($mode.'-'.$i, $r);
    echo json_encode(array_diff_key($r, ['id' => 1]));
} elseif ($mode === 'final') {
    $before = $load('baseline');
    $after = $rows();
    foreach ($before as $t => $old) {
        $counts = array_count_values(array_map('serialize', $after[$t]));
        foreach ($old as $r) {
            $key = serialize($r);
            $check(($counts[$key] ?? 0) > 0, 'Existing row changed after delete '.$t);
            $counts[$key]--;
        }
    }$delta = [];
    foreach ($after as $t => $v) {
        $delta[$t] = count($v) - count($before[$t]);
    }$expected = ['users' => 3, 'categories' => 1, 'products' => 1, 'service_orders' => 2, 'number_sequences' => 2];
    foreach ($delta as $t => $n) {
        $check($n === ($expected[$t] ?? 0), 'Unexpected retained delta '.$t);
    }echo json_encode(['verdict' => 'pass', 'baseline_rows_identical_after_delete' => true, 'retained_deltas' => $delta, 'no_mysql_connections_left_after_process_exit' => true]);
} elseif ($mode === 'proof' || $mode === 'delete') {
    $own = $load('own');
    $before = $load('baseline');
    $after = $rows();
    foreach ($before as $t => $old) {
        $counts = array_count_values(array_map('serialize', $after[$t]));
        foreach ($old as $r) {
            $key = serialize($r);
            $check(($counts[$key] ?? 0) > 0, 'Existing row changed '.$t);
            $counts[$key]--;
        }
    }
    $t = [$load('tts-0'), $load('tts-1')];
    $s = [$load('sale-0'), $load('sale-1')];
    $numbers = array_column($t, 'number');
    sort($numbers);
    $outcomes = array_column($s, 'result');
    sort($outcomes);
    $check($numbers === ['TTS-3101-001', 'TTS-3101-002'], 'TTS race numbers wrong');
    $check($outcomes === ['shortage', 'success'], 'Sale race results wrong');
    $sale = Sale::whereIn('created_by', $own['admins'])->with('items')->firstOrFail();
    $check($sale->number === 'JL-3101-001' && $sale->items->count() === 1, 'Sale number/items wrong');
    $check(Product::findOrFail($own['product'])->stock === 0, 'Final stock wrong');
    $check((int) DB::table('number_sequences')->where('prefix', 'JL')->where('period', '3101')->value('last_seq') === 1, 'JL sequence wrong');
    $check((int) DB::table('number_sequences')->where('prefix', 'TTS')->where('period', '3101')->value('last_seq') === 2, 'TTS sequence wrong');
    $delta = [];
    foreach ($after as $k => $v) {
        $delta[$k] = count($v) - count($before[$k]);
    }
    $expected = ['users' => 3, 'categories' => 1, 'products' => 1, 'sales' => 1, 'sale_items' => 1, 'service_orders' => 2, 'number_sequences' => 2];
    foreach ($delta as $k => $v) {
        $check($v === ($expected[$k] ?? 0), 'Unexpected delta '.$k);
    }
    $orphan = DB::table('sale_items')->leftJoin('sales', 'sales.id', '=', 'sale_items.sale_id')->whereNull('sales.id')->count();
    $check($orphan === 0, 'Orphan items');
    $report = ['verdict' => 'pass', 'actual_actions' => [CreateServiceOrder::class, CreateSale::class], 'tts_numbers' => $numbers, 'sales_outcomes' => $outcomes, 'stock' => 0, 'sale_items' => 1, 'orphan_items' => $orphan, 'tts_sequence' => 2, 'jl_sequence' => 1, 'baseline_rows_identical' => true, 'table_count_deltas' => $delta];
    if ($mode === 'delete') {
        auth()->login(User::findOrFail($own['admins'][0]));
        app(DeleteSale::class)->handle($sale->id);
        $check(Product::findOrFail($own['product'])->stock === 1, 'Delete not restored');
        try {
            app(DeleteSale::class)->handle($sale->id);
            throw new RuntimeException('Second delete accepted');
        } catch (ModelNotFoundException) {
        }$check(Product::findOrFail($own['product'])->stock === 1, 'Restored twice');
        $report['delete_own_fixture_only'] = true;
        $report['restore_once'] = true;
        $report['retained_stock'] = 1;
    }
    echo json_encode($report);
} else {
    throw new RuntimeException('Unknown mode');
}
