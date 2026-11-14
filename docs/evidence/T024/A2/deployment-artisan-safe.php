<?php
ini_set('display_errors', '0');
$command = $argv[1] ?? '';
$phase = 'preflight';
try {
    $allowed = ['migrate', 'config:cache', 'route:cache', 'view:cache', 'db:seed'];
    if (!in_array($command, $allowed, true)) {
        throw new RuntimeException('Command outside bounded deployment scope');
    }
    $root = '/var/www/demo-repair-app/current';
    chdir($root);
    require $root.'/vendor/autoload.php';
    putenv('ADMIN_INITIAL_PASSWORD');
    unset($_ENV['ADMIN_INITIAL_PASSWORD'], $_SERVER['ADMIN_INITIAL_PASSWORD']);
    $private = Dotenv\Dotenv::parse(file_get_contents($root.'/.env'));
    $expected = ['APP_ENV'=>'production','APP_DEBUG'=>'false','APP_URL'=>'https://demo-repair-app.scannara.asia','APP_TIMEZONE'=>'Asia/Makassar','DB_CONNECTION'=>'mariadb','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_DATABASE'=>'demo_repair_app','DB_USERNAME'=>'new_gym','SESSION_SECURE_COOKIE'=>'true'];
    foreach ($expected as $key => $value) {
        if (($private[$key] ?? null) !== $value) throw new RuntimeException('Fixed target configuration mismatch');
    }
    if (empty($private['APP_KEY']) || empty($private['DB_PASSWORD']) || !is_file('/var/www/demo-repair-app/.deploy-pending') || !is_file($root.'/storage/framework/down')) throw new RuntimeException('Private key or maintenance guard missing');
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (!app()->isProduction() || config('app.debug') || config('database.default') !== 'mariadb' || config('database.connections.mariadb.host') !== '127.0.0.1' || (string)config('database.connections.mariadb.port') !== '3306' || config('database.connections.mariadb.database') !== 'demo_repair_app' || config('database.connections.mariadb.username') !== 'new_gym' || config('app.key') !== $private['APP_KEY']) throw new RuntimeException('Framework target guard mismatch');
    $db = Illuminate\Support\Facades\DB::connection();
    $identity = $db->selectOne('SELECT DATABASE() AS db, VERSION() AS version, CURRENT_USER() AS account, @@hostname AS hostname');
    if ($identity->db !== 'demo_repair_app' || $identity->account !== 'new_gym@localhost' || !str_contains($identity->version, '10.11.14-MariaDB')) throw new RuntimeException('Database identity mismatch');
    if ($command === 'migrate') {
        $phase = 'production-admin-password-validation';
        if (empty($private['ADMIN_INITIAL_PASSWORD'])) throw new RuntimeException('Initial admin password missing');
        Illuminate\Support\Facades\Validator::make(['password'=>$private['ADMIN_INITIAL_PASSWORD']], ['password'=>['required','string',Illuminate\Validation\Rules\Password::default()]])->validate();
        $phase = 'immediate-empty-target-check';
        $tables = $db->select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'demo_repair_app'");
        if ($tables !== []) throw new RuntimeException('Target is not empty; first migration stopped');
        echo json_encode(['phase'=>'pre-migration','status'=>'PASS','database'=>$identity->db,'server'=>$identity->version,'account'=>$identity->account,'hostname'=>$identity->hostname,'tables'=>0,'production_password_rule'=>'PASS','backup'=>'DEFERRED_D22']), PHP_EOL;
    }
    $phase = $command;
    $arguments = in_array($command, ['migrate','db:seed'], true) ? ['--force'=>true,'--no-interaction'=>true] : [];
    $exit = Illuminate\Support\Facades\Artisan::call($command, $arguments);
    if ($exit !== 0) throw new RuntimeException('Artisan returned nonzero status', $exit);
    echo json_encode(['phase'=>$command,'status'=>'PASS','exit'=>0,'raw_console_output'=>'suppressed','maintenance'=>'retained']), PHP_EOL;
} catch (Throwable $error) {
    $safe = ['phase'=>$phase,'command'=>$command,'status'=>'FAIL','exception_class'=>get_class($error),'code'=>is_int($error->getCode()) ? $error->getCode() : 0,'raw_message'=>'suppressed','maintenance'=>'retained'];
    if ($error instanceof PDOException || $error instanceof Illuminate\Database\QueryException) {
        $safe['sqlstate'] = $error->errorInfo[0] ?? null;
        $safe['vendor_code'] = $error->errorInfo[1] ?? null;
    }
    echo json_encode($safe), PHP_EOL;
    exit(2);
}
