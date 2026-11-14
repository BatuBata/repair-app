<?php
declare(strict_types=1);
$qa = '/home/muzib/deploy-demo-repair-app/qa-mariadb';
$src = '/var/www/demo-repair-app/current';
function verify(bool $value, string $message): void { if (!$value) throw new RuntimeException($message); }
verify(realpath($qa) === $qa && (fileperms($qa) & 0777) === 0700, 'Private QA runtime required.');
$private = json_decode(file_get_contents($qa.'/private.json'), true, flags: JSON_THROW_ON_ERROR);
$private += [
    'APP_CONFIG_CACHE' => $qa.'/cache/config.php', 'APP_PACKAGES_CACHE' => $qa.'/cache/packages.php',
    'APP_SERVICES_CACHE' => $qa.'/cache/services.php', 'APP_ROUTES_CACHE' => $qa.'/cache/routes.php',
    'APP_EVENTS_CACHE' => $qa.'/cache/events.php', 'VIEW_COMPILED_PATH' => $qa.'/storage/framework/views',
    'LARAVEL_STORAGE_PATH' => $qa.'/storage',
];
foreach ($private as $key => $value) { putenv($key.'='.$value); $_ENV[$key] = $_SERVER[$key] = $value; }
require $src.'/vendor/autoload.php';
$app = require $src.'/bootstrap/app.php';
$app->useEnvironmentPath($qa.'/env');
$app->useStoragePath($qa.'/storage');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
verify($app->environmentPath() === $qa.'/env' && $app->storagePath() === $qa.'/storage', 'Environment/storage isolation failed.');
foreach ([$app->getCachedConfigPath(), $app->getCachedPackagesPath(), $app->getCachedServicesPath(), $app->getCachedRoutesPath(), $app->getCachedEventsPath(), config('view.compiled')] as $path) {
    verify(str_starts_with($path, $qa.'/'), 'Cache isolation failed.');
}
$connection = Illuminate\Support\Facades\DB::connection();
verify($connection->getDatabaseName() === 'demo_repair_app_t024_verify' && $connection->getConfig('unix_socket') === $qa.'/mariadb.sock' && $connection->getConfig('username') === 't024_app', 'STOP: unexpected database config.');
$identity = (array) $connection->selectOne('SELECT DATABASE() AS db, @@socket AS socket, @@datadir AS datadir, @@skip_networking AS skip_networking');
verify($identity['db'] === 'demo_repair_app_t024_verify' && $identity['socket'] === $qa.'/mariadb.sock' && rtrim($identity['datadir'], '/') === $qa.'/data' && (int) $identity['skip_networking'] === 1, 'STOP: unexpected actual database identity.');
Carbon\CarbonImmutable::setTestNow(new Carbon\CarbonImmutable('2033-01-15 10:00:00 Asia/Makassar'));
