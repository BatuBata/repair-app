<?php
declare(strict_types=1);
require __DIR__.'/boot.php';

use App\Actions\Sales\CreateSale;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StockEntry;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Services\Reports\Report;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

$mode = $argv[1] ?? '';
function save(string $name, array $value): void { global $qa; file_put_contents($qa.'/'.$name.'.json', json_encode($value, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n"); }
function load(string $name): array { global $qa; return json_decode(file_get_contents($qa.'/'.$name.'.json'), true, flags: JSON_THROW_ON_ERROR); }

if ($mode === 'init') {
    verify(count(Schema::getTables()) === 0, 'STOP: isolated schema must be empty before migrate.');
    verify(Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) === 0, 'Migration failed.');
    file_put_contents($qa.'/migration.log', Artisan::output());
    verify(Artisan::call('db:seed', ['--force' => true, '--no-interaction' => true]) === 0, 'Synthetic seed failed.');
    file_put_contents($qa.'/seed.log', Artisan::output());
    verify(User::count() === 1 && Category::count() === 9, 'Initial seed counts mismatch.');
    $admin = User::where('username', 'admin')->firstOrFail();
    $second = User::create(['name' => 'QA second', 'username' => 't024_second', 'password' => bin2hex(random_bytes(24)), 'role' => 'admin', 'is_active' => true]);
    $stock = Product::create(['name' => 'T024 numbering', 'cost_price' => 25000, 'sell_price' => 40000, 'stock' => 8]);
    $last = Product::create(['name' => 'T024 last item', 'cost_price' => 500, 'sell_price' => 1000, 'stock' => 1]);
    $engines = DB::select('SELECT table_name, engine FROM information_schema.tables WHERE table_schema = DATABASE()');
    verify(count($engines) === 20 && count(array_filter($engines, fn ($t) => $t->engine !== 'InnoDB')) === 0, 'Schema engine/table count mismatch.');
    $columns = collect(Schema::getColumns('products'))->keyBy('name');
    verify(preg_match('/^int(?:\(\d+\))? unsigned$/', $columns['stock']['type']) === 1, 'Stock unsigned schema mismatch.');
    $failures = [];
    foreach (['unique' => fn () => DB::table('users')->insert(['name' => 'duplicate', 'username' => 'admin', 'password' => 'synthetic']),
        'foreign_key' => fn () => DB::table('stock_entries')->insert(['product_id' => 999999, 'created_by' => $admin->id, 'qty' => 1, 'cost_price' => 1, 'entry_date' => '2033-01-15']),
        'unsigned' => fn () => DB::table('products')->where('id', $stock->id)->update(['stock' => -1]),
    ] as $name => $action) {
        DB::beginTransaction();
        try { $action(); throw new RuntimeException('Expected constraint rejection missing: '.$name); }
        catch (Illuminate\Database\QueryException $e) {
            $code = (int) $e->errorInfo[1];
            verify($code === ['unique' => 1062, 'foreign_key' => 1452, 'unsigned' => 1264][$name], 'Unexpected constraint code: '.$name);
            $failures[$name] = $code;
        } finally { DB::rollBack(); }
    }
    save('fixtures', ['admins' => [$admin->id, $second->id], 'stock' => $stock->id, 'last' => $last->id]);
    save('schema-proof', ['migrate_not_fresh' => true, 'seed_users' => 1, 'seed_categories' => 9, 'table_count' => count($engines), 'all_innodb' => true, 'stock_type' => $columns['stock']['type'], 'constraint_rejections' => $failures, 'isolated_env_cache_storage' => true]);
    echo "PASS isolated migrate/seed,20 InnoDB tables,FK/unique/unsigned enforced\n";
} elseif ($mode === 'worker') {
    $kind = $argv[2]; $index = (int) $argv[3]; $owned = load('fixtures');
    verify(in_array($kind, ['TTS', 'JL', 'last'], true) && in_array($index, [0, 1], true), 'Invalid worker input.');
    $admin = User::findOrFail($owned['admins'][$index]);
    auth()->setUser($admin);
    $locks = [];
    DB::listen(function ($query) use (&$locks) { if (str_contains($query->sql, '`products`') && str_contains($query->sql, 'for update')) $locks[] = round($query->time, 2); });
    touch($qa.'/'.$kind.'-ready-'.$index);
    $deadline = microtime(true) + 15;
    while (!file_exists($qa.'/'.$kind.'-go')) { verify(microtime(true) < $deadline, 'Worker barrier timeout.'); usleep(10000); }
    $entered = microtime(true); $attempts = 0;
    try {
        $number = DB::transaction(function () use ($kind, $owned, $admin, &$attempts) {
            $attempts++;
            if ($kind === 'TTS') {
                $record = app(CreateServiceOrder::class)->create(['customer_name' => 'T024 synthetic', 'customer_phone' => '081234567890', 'category_id' => Category::firstOrFail()->id, 'complaint' => 'Isolated concurrency'], $admin);
            } else {
                $record = app(CreateSale::class)->handle(['sold_on' => '2033-01-15', 'customer_name' => 'T024 synthetic', 'customer_phone' => '', 'warranty_days' => '0', 'items' => [['product_id' => $owned[$kind === 'last' ? 'last' : 'stock'], 'qty' => 1, 'price' => $kind === 'last' ? 1000 : 40000]]]);
            }
            usleep(300000);
            return $record->number;
        }, 5);
        $result = ['outcome' => 'success', 'number' => $number];
    } catch (ValidationException $e) {
        verify($kind === 'last' && array_key_exists('items.0.qty', $e->errors()), 'Unexpected validation failure.');
        $result = ['outcome' => 'shortage', 'qty_error' => true];
    }
    $result += ['index' => $index, 'kind' => $kind, 'entered' => $entered, 'finished' => microtime(true), 'attempts' => $attempts, 'product_lock_ms' => $locks];
    save($kind.'-worker-'.$index, $result);
    echo json_encode($result, JSON_THROW_ON_ERROR)."\n";
} elseif ($mode === 'run') {
    $evidence = [];
    foreach (['TTS', 'JL', 'last'] as $kind) {
        verify(!file_exists($qa.'/'.$kind.'-go'), 'Do not rerun committed QA fixtures blindly.');
        $workers = [];
        for ($i = 0; $i < 2; $i++) {
            $worker = new Process([PHP_BINARY, __FILE__, 'worker', $kind, (string) $i], $qa);
            $worker->setTimeout(30); $worker->start(); $workers[] = $worker;
        }
        $deadline = microtime(true) + 15;
        while (!file_exists($qa.'/'.$kind.'-ready-0') || !file_exists($qa.'/'.$kind.'-ready-1')) { verify(microtime(true) < $deadline, 'Parent barrier timeout.'); usleep(10000); }
        touch($qa.'/'.$kind.'-go');
        foreach ($workers as $i => $worker) {
            $worker->wait();
            file_put_contents($qa.'/'.$kind.'-process-'.$i.'.log', $worker->getOutput().$worker->getErrorOutput());
            verify($worker->isSuccessful(), 'Worker failed; inspect private process log.');
        }
        $rows = [load($kind.'-worker-0'), load($kind.'-worker-1')];
        verify(max(array_column($rows, 'entered')) < min(array_column($rows, 'finished')), 'Concurrent attempts did not overlap.');
        $outcomes = array_column($rows, 'outcome'); sort($outcomes);
        verify($outcomes === ($kind === 'last' ? ['shortage', 'success'] : ['success', 'success']), 'Wrong race outcomes.');
        if ($kind !== 'last') {
            $numbers = array_column($rows, 'number'); sort($numbers);
            verify($numbers === [$kind.'-3301-001', $kind.'-3301-002'], 'Concurrent numbers not exact/unique.');
        }
        $evidence[$kind] = $rows;
    }
    $owned = load('fixtures');
    verify(Product::findOrFail($owned['stock'])->stock === 6 && Product::findOrFail($owned['last'])->stock === 0, 'Stock conservation failed.');
    verify(Sale::count() === 3 && DB::table('sale_items')->count() === 3 && ServiceOrder::count() === 2 && ServiceOrder::distinct()->count('tracking_token') === 2, 'Committed document/item counts wrong.');
    verify((int) DB::table('number_sequences')->where(['prefix' => 'JL', 'period' => '3301'])->value('last_seq') === 3, 'Shortage changed sequence.');
    save('concurrency-proof', ['workers' => $evidence, 'actual_actions' => [CreateServiceOrder::class, CreateSale::class], 'normal_stock' => 6, 'last_stock' => 0, 'sales' => 3, 'sale_items' => 3, 'service_orders' => 2, 'distinct_tracking_tokens' => 2, 'jl_sequence' => 3]);
    auth()->setUser(User::findOrFail($owned['admins'][0]));
    $order = ServiceOrder::firstOrFail();
    $order->update(['status' => 'selesai', 'invoiced_on' => '2033-01-15']);
    $order->items()->create(['type' => 'jasa', 'description' => 'T024 report fixture', 'qty' => 2, 'price' => 4000, 'cost' => 1500]);
    Expense::create(['spent_on' => '2033-01-15', 'category' => 'bensin', 'description' => 'T024 expense', 'amount' => 1000, 'created_by' => $owned['admins'][0]]);
    StockEntry::create(['product_id' => $owned['stock'], 'qty' => 2, 'cost_price' => 1000, 'entry_date' => '2033-01-15', 'created_by' => $owned['admins'][0]]);
    $report = app(Report::class)->build('2033-01-15', '2033-01-15');
    $expected = ['service' => '8000', 'sales' => '81000', 'revenue' => '89000', 'cost' => '53500', 'gross' => '35500', 'expenses' => '1000', 'net' => '34500', 'restock' => '2000'];
    foreach ($expected as $key => $value) verify($report[$key] === $value, 'Report total mismatch: '.$key);
    verify(count($report['rows']) === 5 && $report['days'][0]['amount'] === '89000', 'Report rows/daily total mismatch.');
    save('report-proof', ['expected' => $expected, 'actual' => array_intersect_key($report, $expected), 'rows' => 5, 'daily_amount' => $report['days'][0]['amount']]);
    echo "PASS overlappingTTS/JL/last-item actions; stock/number conservation; exactreport totals\n";
} else { throw new RuntimeException('Unknown mode.'); }
