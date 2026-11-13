<?php

use App\Http\Controllers\TrackingController;
use App\Models\ServiceOrder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

require getcwd().'/vendor/autoload.php';
$root = getenv('QA_RUNTIME');
if (! $root || ! str_starts_with($root, '/private/tmp/t016-mysql-')) {
    throw new RuntimeException('Task runtime required');
}
foreach (['/storage/framework/views', '/storage/logs'] as $dir) {
    if (! is_dir($root.$dir)) {
        mkdir($root.$dir, 0700, true);
    }
}
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($root.'/storage');
$app->make(Kernel::class)->bootstrap();
$source = Dotenv\Dotenv::parse(file_get_contents(getcwd().'/.env'));
config(['database.default' => 'mysql', 'database.connections.mysql.url' => null, 'database.connections.mysql.host' => $source['DB_HOST'] ?? '127.0.0.1', 'database.connections.mysql.port' => $source['DB_PORT'] ?? '3306', 'database.connections.mysql.database' => 'repair_app_test', 'database.connections.mysql.username' => $source['DB_USERNAME'] ?? 'root', 'database.connections.mysql.password' => $source['DB_PASSWORD'] ?? '', 'database.connections.mysql.unix_socket' => $source['DB_SOCKET'] ?? '']);
unset($source);
DB::purge('mysql');
$own = unserialize(file_get_contents($root.'/own'), ['allowed_classes' => false]);
$baseline = unserialize(file_get_contents($root.'/baseline'), ['allowed_classes' => false]);
$rows = function () {
    $r = [];
    foreach (DB::select('SHOW TABLES') as $t) {
        $n = array_values((array) $t)[0];
        $r[$n] = DB::table($n)->get()->map(fn ($row) => (array) $row)->all();
    }ksort($r);

    return $r;
};
$before = $rows();
$order = ServiceOrder::where('category_id', $own['category'])->firstOrFail();
$token = $order->tracking_token;
$swap = implode('', array_map(fn ($c) => ctype_lower($c) ? strtoupper($c) : strtolower($c), str_split($token)));
if ($token === $swap) {
    throw new RuntimeException('Fixture token has no caseable characters');
}
$column = DB::selectOne("SHOW FULL COLUMNS FROM service_orders WHERE Field = 'tracking_token'");
$lookup = ServiceOrder::where('tracking_token', $swap)->value('id');
$controller = app(TrackingController::class);
try {
    $controller->show($swap);
    throw new RuntimeException('Swapcase token accepted');
} catch (HttpException$e) {
    $wrong = $e->getStatusCode();
    if ($wrong !== 404) {
        throw new RuntimeException('Wrongcase did not reject404');
    }
}
$response = $controller->show($token);
$body = $response->getContent();
$data = $response->getOriginalContent()->getData();
if ($response->getStatusCode() !== 200 || $lookup !== $order->id) {
    throw new RuntimeException('Exact lookup/collation proof failed');
}
foreach (['customer_phone', 'customer_address'] as $key) {
    if (array_key_exists($key, $data['order']->getAttributes())) {
        throw new RuntimeException('Private field selected into public view');
    }
}
$after = $rows();
if ($before !== $after) {
    throw new RuntimeException('Read-only check changed database');
}foreach ($baseline as $t => $old) {
    $counts = array_count_values(array_map('serialize', $after[$t]));
    foreach ($old as $r) {
        $key = serialize($r);
        if (($counts[$key] ?? 0) < 1) {
            throw new RuntimeException('Baseline changed '.$t);
        }$counts[$key]--;
    }
}
echo json_encode(['verdict' => 'pass', 'actual_controller' => TrackingController::class.'::show', 'column_collation' => $column->Collation, 'case_insensitive_raw_lookup_returns_own_id' => $lookup === $order->id, 'wrongcase_status' => $wrong, 'exact_status' => $response->getStatusCode(), 'noindex' => $response->headers->get('X-Robots-Tag') === 'noindex', 'no_store_private' => $response->headers->get('Cache-Control') === 'no-store, private', 'private_fields_absent_from_public_view' => true, 'no_livewire_snapshot' => ! str_contains($body, 'wire:snapshot'), 'database_identical_before_after' => true, 'original_baseline_preserved' => true, 'view_storage_private' => str_starts_with(config('view.compiled'), $root)])."\n";
