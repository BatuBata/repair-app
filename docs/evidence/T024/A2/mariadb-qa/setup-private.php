<?php
declare(strict_types=1);
$qa = '/home/muzib/deploy-demo-repair-app/qa-mariadb';
if (realpath($qa) !== $qa || (fileperms($qa) & 0777) !== 0700 || file_exists($qa.'/env/.env')) {
    throw new RuntimeException('Private first-use QA directory required.');
}
$socket = $qa.'/mariadb.sock';
$pdo = new PDO('mysql:unix_socket='.$socket, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$identity = $pdo->query('SELECT @@socket AS socket, @@datadir AS datadir, @@skip_networking AS skip_networking, VERSION() AS version')->fetch(PDO::FETCH_ASSOC);
if ($identity['socket'] !== $socket || rtrim($identity['datadir'], '/') !== $qa.'/data' || (int) $identity['skip_networking'] !== 1 || !str_starts_with($identity['version'], '10.11.')) {
    throw new RuntimeException('STOP: isolated MariaDB identity mismatch before writes.');
}
$db = 'demo_repair_app_t024_verify';
$exists = $pdo->query("SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = '$db'")->fetchColumn();
if ((int) $exists !== 0) throw new RuntimeException('STOP: QA schema already exists.');
$password = bin2hex(random_bytes(24));
$pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("CREATE USER 't024_app'@'localhost' IDENTIFIED BY ".$pdo->quote($password));
$pdo->exec("GRANT ALL ON `$db`.* TO 't024_app'@'localhost'");
$env = [
    'APP_ENV' => 'testing', 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)), 'APP_DEBUG' => 'false',
    'APP_URL' => 'https://qa.invalid', 'APP_TIMEZONE' => 'Asia/Makassar', 'APP_LOCALE' => 'id',
    'BCRYPT_ROUNDS' => '4', 'DB_CONNECTION' => 'mariadb', 'DB_URL' => '', 'DB_HOST' => 'localhost',
    'DB_SOCKET' => $socket, 'DB_DATABASE' => $db, 'DB_USERNAME' => 't024_app', 'DB_PASSWORD' => $password,
    'ADMIN_INITIAL_PASSWORD' => 'QAa1!'.bin2hex(random_bytes(24)), 'SESSION_DRIVER' => 'array',
    'CACHE_STORE' => 'array', 'QUEUE_CONNECTION' => 'sync', 'LOG_CHANNEL' => 'single',
    'APP_MAINTENANCE_DRIVER' => 'file', 'FILESYSTEM_DISK' => 'local',
];
file_put_contents($qa.'/env/.env', implode("\n", array_map(fn ($k, $v) => $k.'="'.$v.'"', array_keys($env), $env))."\n");
chmod($qa.'/env/.env', 0600);
file_put_contents($qa.'/private.json', json_encode($env, JSON_THROW_ON_ERROR));
chmod($qa.'/private.json', 0600);
$identity += ['identity_verified_before_sql_writes' => true, 'database' => $db, 'production_db_accessed' => false];
file_put_contents($qa.'/instance-identity.json', json_encode($identity, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n");
echo "PASS isolated identity, new task-only schema and private synthetic credentials\n";
