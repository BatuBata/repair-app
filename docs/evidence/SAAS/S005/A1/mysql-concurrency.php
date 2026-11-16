<?php

// Runs the existing stock race against a newly created local database; retains all evidence fixtures.
use App\Models\Tenant;
use Dotenv\Dotenv;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 5);
require $root.'/vendor/autoload.php';
$local = Dotenv::parse(file_get_contents($root.'/.env'));
$database = 'saas_s005_'.gmdate('Ymd_His').'_'.getmypid();
$environment = [
    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_URL' => '',
    'DB_HOST' => $local['DB_HOST'] ?? '127.0.0.1', 'DB_PORT' => $local['DB_PORT'] ?? '3306',
    'DB_USERNAME' => $local['DB_USERNAME'] ?? 'root', 'DB_PASSWORD' => $local['DB_PASSWORD'] ?? '',
    'DB_DATABASE' => $database, 'QA_DATABASE' => $database,
];
if (! in_array($environment['DB_HOST'], ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('Only a new local S005 database is allowed.');
}
foreach ($environment as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, 'S005 runner failed: '.get_class($error).PHP_EOL);
    exit(1);
});
$server = new PDO('mysql:host='.$environment['DB_HOST'].';port='.$environment['DB_PORT'], $environment['DB_USERNAME'], $environment['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?');
$stmt->execute([$database]);
if ((int) $stmt->fetchColumn() !== 0) {
    throw new RuntimeException('Database already exists; refusing changes.');
}
$server->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
if (Artisan::call('migrate', ['--force' => true]) !== 0) {
    throw new RuntimeException('Migration failed on new S005 database.');
}
$tenants = [Tenant::findOrFail(1), Tenant::create(['name' => 'Synthetic S005 B'])];
$run = function (array $arguments, array $env) use ($root): array {
    $process = new Process([PHP_BINARY, 'tests/Feature/Sales/mysql-concurrency.php', ...$arguments], $root, $env);
    $process->setTimeout(30);
    $process->mustRun();

    return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
};
$results = [];
foreach ($tenants as $tenant) {
    $runtime = '/private/tmp/saas-s005-'.bin2hex(random_bytes(8));
    mkdir($runtime, 0700);
    $env = [...$environment, 'QA_RUNTIME' => $runtime, 'QA_TENANT' => (string) $tenant->id];
    $inventory = $run(['inventory'], $env);
    $run(['init'], $env);
    $workers = [];
    foreach ([0, 1] as $index) {
        $process = new Process([PHP_BINARY, 'tests/Feature/Sales/mysql-concurrency.php', 'worker', (string) $index], $root, $env);
        $process->setTimeout(30);
        $process->start();
        $workers[] = $process;
    }
    $deadline = microtime(true) + 8;
    while (! file_exists($runtime.'/ready-0') || ! file_exists($runtime.'/ready-1')) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('Stock workers did not reach the finite barrier.');
        }
        usleep(10000);
    }
    touch($runtime.'/go');
    foreach ($workers as $process) {
        $process->wait();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Stock worker failed.');
        }
    }
    $proof = $run(['proof'], $env);
    $owned = unserialize(file_get_contents($runtime.'/owned'), ['allowed_classes' => false]);
    $sale = DB::table('sales')->where('tenant_id', $tenant->id)->first();
    $results[] = ['tenant' => $tenant->id, 'inventory' => $inventory, 'proof' => $proof, 'sale_number' => $sale->number, 'private_runtime' => $runtime, 'fixture_tenant' => $owned['tenant']];
}
if ($results[0]['sale_number'] !== $results[1]['sale_number']) {
    throw new RuntimeException('JL counters were not independent.');
}
echo json_encode(['result' => 'PASS', 'database' => $database, 'database_retained' => true, 'existing_databases_touched' => false, 'tenants' => $results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
