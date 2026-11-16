<?php

// Standalone evidence runner; creates and retains one new local database, never resets an existing one.
use App\Models\Tenant;
use App\Services\DocumentNumber;
use App\Support\TenantContext;
use Dotenv\Dotenv;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

$root = dirname(__DIR__, 5);
require $root.'/vendor/autoload.php';
$worker = ($argv[1] ?? '') === 'worker';
$local = $worker ? [] : Dotenv::parse(file_get_contents($root.'/.env'));
$environment = [
    'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_URL' => '',
    'DB_HOST' => $local['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
    'DB_PORT' => $local['DB_PORT'] ?? getenv('DB_PORT') ?: '3306',
    'DB_USERNAME' => $local['DB_USERNAME'] ?? getenv('DB_USERNAME'),
    'DB_PASSWORD' => $local['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
    'DB_DATABASE' => $worker ? getenv('DB_DATABASE') : 'saas_s004_'.gmdate('Ymd_His').'_'.getmypid(),
];
if (! in_array($environment['DB_HOST'], ['127.0.0.1', 'localhost', '::1'], true) || ! preg_match('/\Asaas_s004_[0-9_]+\z/', $environment['DB_DATABASE'])) {
    throw new RuntimeException('Only a new, dedicated local S004 database is allowed.');
}
foreach ($environment as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, 'S004 runner failed: '.get_class($error).PHP_EOL);
    exit(1);
});
config(['database.default' => 'mysql', 'database.connections.mysql.url' => null]);
foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
    config(['database.connections.mysql.'.$key => $environment['DB_'.strtoupper($key)]]);
}
DB::purge('mysql');
if ($worker) {
    [$mode, $tenantId, $prefix, $start] = array_slice($argv, 1);
    app(TenantContext::class)->set(Tenant::findOrFail($tenantId));
    while (microtime(true) < (float) $start) {
        usleep(1000);
    }
    $entered = microtime(true);
    $number = DB::transaction(function () use ($prefix) {
        $number = app(DocumentNumber::class)->next($prefix, new DateTimeImmutable('2035-01-01T00:00:00+08:00'));
        usleep(150000);

        return $number;
    }, 5);
    echo json_encode(['tenant' => (int) $tenantId, 'prefix' => $prefix, 'number' => $number, 'entered' => $entered, 'finished' => microtime(true)], JSON_THROW_ON_ERROR);
    exit;
}
$server = new PDO('mysql:host='.$environment['DB_HOST'].';port='.$environment['DB_PORT'], $environment['DB_USERNAME'], $environment['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ?');
$stmt->execute([$environment['DB_DATABASE']]);
if ((int) $stmt->fetchColumn() !== 0) {
    throw new RuntimeException('Database already exists; refusing any changes.');
}
$server->exec('CREATE DATABASE `'.$environment['DB_DATABASE'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
if (Artisan::call('migrate', ['--force' => true]) !== 0) {
    throw new RuntimeException('Migration failed on new S004 database.');
}
$tenants = [Tenant::findOrFail(1), Tenant::create(['name' => 'Synthetic S004 B'])];
$results = [];
foreach ([0, 4] as $offset) {
    $start = microtime(true) + 1;
    $processes = [];
    foreach ($tenants as $tenant) {
        foreach (['TTS', 'JL'] as $prefix) {
            for ($i = 0; $i < 4; $i++) {
                $process = new Process([PHP_BINARY, __FILE__, 'worker', (string) $tenant->id, $prefix, (string) $start], $root, $environment);
                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }
        }
    }
    $round = [];
    foreach ($processes as $process) {
        $process->wait();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Child failed; inspect locally without logging credentials.');
        }
        $round[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    }
    foreach ($tenants as $tenant) {
        foreach (['TTS', 'JL'] as $prefix) {
            $group = array_values(array_filter($round, fn ($row) => $row['tenant'] === $tenant->id && $row['prefix'] === $prefix));
            $actual = array_column($group, 'number');
            sort($actual);
            $expected = array_map(fn ($n) => $prefix.'-3501-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT), range($offset + 1, $offset + 4));
            if ($actual !== $expected || max(array_column($group, 'entered')) >= min(array_column($group, 'finished'))) {
                throw new RuntimeException('Numbers or transaction overlap failed.');
            }
        }
    }
    $results[] = ['offset' => $offset, 'workers' => $round, 'result' => 'PASS'];
}
$counters = DB::table('number_sequences')->orderBy('tenant_id')->orderBy('prefix')->get()->all();
if (count($counters) !== 4 || array_unique(array_column($counters, 'last_seq')) !== [8]) {
    throw new RuntimeException('Unexpected counters.');
}
echo json_encode(['result' => 'PASS', 'database' => $environment['DB_DATABASE'], 'database_retained' => true, 'existing_databases_touched' => false, 'rounds' => $results, 'counters' => $counters], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
