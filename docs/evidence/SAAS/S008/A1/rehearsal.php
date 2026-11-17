<?php

// Local synthetic rehearsal only. Credentials/dumps stay outside Git in a private runtime.
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DocumentNumber;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\Feature\Tenancy\MigrationTest;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$runtime = getenv('S008_RUNTIME');
if (! $runtime || ! preg_match('#^/private/tmp/saas-s008-[a-z0-9-]+$#', $runtime) || file_exists($runtime)) {
    throw new RuntimeException('A new private S008 runtime is required.');
}
mkdir($runtime, 0700);
$stage = 'connect';
function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
function output(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
}
function process(array $args, int $expected = 0): Process
{
    $process = new Process($args, getcwd());
    $process->setTimeout(120);
    $process->run();
    check($process->getExitCode() === $expected, 'Unexpected child process exit code.');

    return $process;
}
function connectDatabase(string $name): void
{
    check((bool) preg_match('/^saas_s008_[a-z0-9_]+$/', $name), 'S008 DB allowlist');
    config(['database.connections.mysql.database' => $name]);
    DB::purge('mysql');
}
function migrateTenant(): void
{
    check(Artisan::call('migrate', ['--path' => database_path('migrations/2026_11_17_000000_add_tenant_foundation.php'), '--realpath' => true, '--force' => true]) === 0, 'Tenant migration');
}
function wholeDatabaseHash(): string
{
    $snapshot = [];
    foreach (Schema::getTables(DB::getDatabaseName()) as $table) {
        $name = $table['name'];
        $rows = DB::table($name)->get()->map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR))->all();
        sort($rows);
        $foreignKeys = Schema::getForeignKeys($name);
        foreach ($foreignKeys as &$foreignKey) {
            check($foreignKey['foreign_schema'] === DB::getDatabaseName(), 'Cross-database foreign key');
            $foreignKey['foreign_schema'] = 'CURRENT_DATABASE';
        }
        unset($foreignKey);
        $snapshot[$name] = [Schema::getColumns($name), Schema::getIndexes($name), $foreignKeys, $rows];
    }
    ksort($snapshot);

    return hash('sha256', serialize($snapshot));
}
try {
    $local = Dotenv\Dotenv::parse(file_get_contents(getcwd().'/.env'));
    check(in_array($local['DB_HOST'] ?? '', ['127.0.0.1', 'localhost', '::1'], true), 'Loopback only');
    foreach (['host', 'port', 'username', 'password', 'unix_socket'] as $key) {
        config(['database.connections.mysql.'.$key => $local['DB_'.strtoupper($key)] ?? ($key === 'port' ? '3306' : ($key === 'unix_socket' ? '' : null))]);
    }
    config(['database.default' => 'mysql', 'database.connections.mysql.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
    $pdo = new PDO('mysql:host='.$local['DB_HOST'].';port='.($local['DB_PORT'] ?? 3306), $local['DB_USERNAME'], $local['DB_PASSWORD'] ?? '');
    $suffix = getenv('S008_DB_SUFFIX');
    check((bool) preg_match('/^[a-z0-9_]{1,28}$/', $suffix ?: ''), 'New explicit DB suffix required');
    $databases = [];
    foreach (['source', 'target', 'failed', 'recovery', 'sqlfail'] as $label) {
        $databases[$label] = 'saas_s008_'.$suffix.'_'.$label;
    }
    $existing = $pdo->query('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($databases as $database) {
        check(! in_array($database, $existing, true), 'Refusing preexisting DB');
    }
    output(['case' => 'preflight', 'databases_absent' => $databases, 'server' => $pdo->query('SELECT VERSION()')->fetchColumn(), 'result' => 'pass']);
    foreach ($databases as $database) {
        $pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    $cnf = $runtime.'/client.cnf';
    $escape = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
    file_put_contents($cnf, "[client]\n".implode("\n", array_map(fn ($key) => $key.'='.$escape($local['DB_'.strtoupper($key)] ?? ($key === 'port' ? 3306 : '')), ['host', 'port', 'user', 'password']))."\n");
    // Laravel names the login DB_USERNAME; mysql option files use user.
    $contents = file_get_contents($cnf);
    file_put_contents($cnf, preg_replace('/^user=.*$/m', 'user='.$escape($local['DB_USERNAME']), $contents));
    chmod($cnf, 0600);
    $stage = 'legacy';
    connectDatabase($databases['source']);
    foreach (glob(database_path('migrations/*.php')) as $file) {
        if (! str_contains($file, 'add_tenant_foundation')) {
            check(Artisan::call('migrate', ['--path' => $file, '--realpath' => true, '--force' => true]) === 0, 'Legacy migration');
        }
    }
    MigrationTest::seedLegacy();
    $baseline = MigrationTest::legacySnapshot();
    $baselineAll = wholeDatabaseHash();
    $tables = count(Schema::getTables(DB::getDatabaseName()));
    mkdir($runtime.'/uploads/logos', 0700, true);
    file_put_contents($runtime.'/uploads/logos/legacy-s008.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
    $uploadHash = hash_file('sha256', $runtime.'/uploads/logos/legacy-s008.png');
    mkdir($runtime.'/backup', 0700);
    $backup = process(['bash', 'deploy/backup.sh', $cnf, $databases['source'], $runtime.'/backup', '-', '-'], 1);
    check(str_contains($backup->getErrorOutput(), 'Local archive saved; Q-04 off-VPS destination missing'), 'Expected pending offsite failure');
    $archives = glob($runtime.'/backup/*.sql.gz');
    check(count($archives) === 1, 'One completed backup expected');
    $archive = $archives[0];
    process(['gzip', '-t', $archive]);
    process(['tar', '-czf', $runtime.'/backup/uploads.tar.gz', '-C', $runtime.'/uploads', '.']);
    $checksums = ['database' => hash_file('sha256', $archive), 'uploads_archive' => hash_file('sha256', $runtime.'/backup/uploads.tar.gz')];
    mkdir($runtime.'/operator-mac', 0700);
    check(copy($archive, $runtime.'/operator-mac/pre-saas.sql.gz'), 'Operator copy DB');
    check(copy($runtime.'/backup/uploads.tar.gz', $runtime.'/operator-mac/uploads.tar.gz'), 'Operator copy uploads');
    check($checksums['database'] === hash_file('sha256', $runtime.'/operator-mac/pre-saas.sql.gz'), 'Operator DB checksum');
    check($checksums['uploads_archive'] === hash_file('sha256', $runtime.'/operator-mac/uploads.tar.gz'), 'Operator uploads checksum');
    mkdir($runtime.'/restored-uploads', 0700);
    process(['tar', '-xzf', $runtime.'/operator-mac/uploads.tar.gz', '-C', $runtime.'/restored-uploads']);
    check($uploadHash === hash_file('sha256', $runtime.'/restored-uploads/logos/legacy-s008.png'), 'Restored upload checksum');
    output(['case' => 'backup-copy-uploads', 'script_exit' => 1, 'pending_offsite_correctly_not_success' => true, 'copy_scope' => 'two local private directories; not off-machine proof', 'archive_sha256' => $checksums, 'uploads_manifest' => ['logos/legacy-s008.png' => $uploadHash], 'all_legacy_tables' => $tables, 'business_inventory' => $baseline, 'result' => 'pass']);
    $stage = 'restore';
    foreach (['target', 'failed', 'recovery'] as $label) {
        process(['bash', 'deploy/restore.sh', $cnf, $databases[$label], $runtime.'/operator-mac/pre-saas.sql.gz', 'RESTORE:'.$databases[$label]]);
        connectDatabase($databases[$label]);
        check($baselineAll === wholeDatabaseHash(), 'All restored tables/columns/rows differ');
    }
    connectDatabase($databases['target']);
    $beforeBad = wholeDatabaseHash();
    file_put_contents($runtime.'/damaged.sql.gz', substr(file_get_contents($archive), 0, -12));
    $bad = process(['bash', 'deploy/restore.sh', $cnf, $databases['target'], $runtime.'/damaged.sql.gz', 'RESTORE:'.$databases['target']], 1);
    check(str_contains($bad->getErrorOutput(), 'MySQL not called'), 'Corrupt archive must fail before import');
    check($beforeBad === wholeDatabaseHash(), 'Corrupt archive mutated target');
    // Valid gzip with an SQL error demonstrates that imports are not atomic.
    file_put_contents($runtime.'/invalid-sql.sql.gz', gzencode('CREATE TABLE s008_partial (id INT); INSERT INTO s008_partial VALUES (1); THIS IS NOT SQL;'));
    $partial = process(['bash', 'deploy/restore.sh', $cnf, $databases['sqlfail'], $runtime.'/invalid-sql.sql.gz', 'RESTORE:'.$databases['sqlfail']], 1);
    check(str_contains($partial->getErrorOutput(), 'target may be partially changed'), 'Partial SQL import warning');
    connectDatabase($databases['sqlfail']);
    check(DB::table('s008_partial')->count() === 1, 'Expected retained partial import');
    output(['case' => 'restore-boundaries', 'three_full_restores_identical_all_tables' => true, 'damaged_gzip_target_unchanged' => true, 'sql_error_partial_target_retained_discard_only' => $databases['sqlfail'], 'result' => 'pass']);
    $stage = 'migration';
    connectDatabase($databases['target']);
    migrateTenant();
    check($baseline === MigrationTest::legacySnapshot(), 'Business fields changed');
    foreach (['users', 'categories', 'products', 'store_settings', 'service_orders', 'sales', 'expenses', 'number_sequences'] as $table) {
        check(DB::table($table)->whereNull('tenant_id')->orWhere('tenant_id', '!=', 1)->count() === 0, 'Backfill '.$table);
        check(! collect(Schema::getColumns($table))->firstWhere('name', 'tenant_id')['nullable'], 'Nullable tenant');
        check(collect(Schema::getForeignKeys($table))->contains(fn ($fk) => $fk['columns'] === ['tenant_id'] && $fk['foreign_table'] === 'tenants'), 'Missing tenant FK');
    }
    check(Tenant::findOrFail(1)->name === 'Anugerah', 'Tenant identity');
    app(TenantContext::class)->set(Tenant::findOrFail(1));
    check(StoreSetting::firstOrFail()->name === 'Anugerah' && StoreSetting::firstOrFail()->logo_path === 'logos/legacy-s008.png', 'Store identity');
    $user = User::findOrFail(1);
    check(Hash::check('synthetic-s008', $user->password), 'Legacy login hash');
    auth()->login($user);
    check(ServiceOrder::findOrFail(1)->technician->id === 2 && ServiceOrder::findOrFail(1)->items()->count() === 2, 'Legacy relations');
    $report = app(Report::class)->build('2026-10-31', '2026-10-31');
    $totals = array_intersect_key($report, array_flip(['service', 'sales', 'revenue', 'cost', 'gross', 'expenses', 'net', 'restock']));
    check(array_values($totals) === ['1250', '500', '1750', '600', '1150', '50', '1100', '1600'], 'Legacy report totals');
    DB::beginTransaction();
    check(app(DocumentNumber::class)->next('TTS', new CarbonImmutable('2026-10-31')) === 'TTS-2610-010', 'TTS continuity');
    check(app(DocumentNumber::class)->next('JL', new CarbonImmutable('2026-10-31')) === 'JL-2610-005', 'JL continuity');
    DB::rollBack();
    check($baseline === MigrationTest::legacySnapshot(), 'Read/number probe altered business data');
    output(['case' => 'migration-and-app', 'all_old_business_fields_identical' => true, 'tenant_id_all_one' => true, 'tenant_keys_required_and_foreign' => true, 'anugerah_identity' => true, 'password_and_parent_child_relations' => true, 'number_continuity_transaction_rolled_back' => true, 'report' => $totals, 'stock' => DB::table('products')->value('stock'), 'result' => 'pass']);
    auth()->logout();
    app(TenantContext::class)->forget();
    $stage = 'rollback';
    check(Artisan::call('migrate:rollback', ['--path' => database_path('migrations/2026_11_17_000000_add_tenant_foundation.php'), '--realpath' => true, '--force' => true]) === 0, 'Single tenant down');
    check($baselineAll === wholeDatabaseHash(), 'Rollback all old rows/schema differ');
    migrateTenant();
    check($baseline === MigrationTest::legacySnapshot(), 'Reapply differs');
    $second = DB::table('tenants')->insertGetId(['name' => 'S008 second synthetic', 'is_active' => true]);
    DB::table('categories')->insert(['tenant_id' => $second, 'name' => 'Elektronik sintetis', 'is_active' => true]);
    $guardBefore = wholeDatabaseHash();
    try {
        Artisan::call('migrate:rollback', ['--path' => database_path('migrations/2026_11_17_000000_add_tenant_foundation.php'), '--realpath' => true, '--force' => true]);
        throw new RuntimeException('Rollback unexpectedly accepted');
    } catch (LogicException $exception) {
        check(str_contains($exception->getMessage(), 'tenant lain'), 'Unexpected rollback error');
    }
    check($guardBefore === wholeDatabaseHash(), 'Guard changed rows/schema');
    output(['case' => 'rollback', 'single_tenant_all_old_schema_and_rows_identical' => true, 'reapply_identical' => true, 'second_tenant_and_duplicate_category_guarded_before_any_change' => true, 'guard_state_sha256' => $guardBefore, 'result' => 'pass']);
    $stage = 'backfill-failure';
    connectDatabase($databases['failed']);
    DB::unprepared("CREATE TRIGGER s008_fail_backfill BEFORE UPDATE ON categories FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'S008 synthetic backfill failure'");
    try {
        migrateTenant();
        throw new RuntimeException('Injected failure unexpectedly accepted');
    } catch (QueryException $exception) {
        check(str_contains($exception->getMessage(), 'S008 synthetic backfill failure'), 'Unexpected injection error');
    }
    check(Schema::hasTable('tenants') && Schema::hasColumn('users', 'tenant_id'), 'Expected MySQL partial DDL');
    check(! Schema::hasColumn('products', 'tenant_id'), 'Unexpected later backfill');
    check(! DB::table('migrations')->where('migration', '2026_11_17_000000_add_tenant_foundation')->exists(), 'Failed migration recorded');
    check($baseline === MigrationTest::legacySnapshot(), 'Failure changed old business fields');
    connectDatabase($databases['recovery']);
    check($baselineAll === wholeDatabaseHash(), 'Recovery restore changed baseline');
    migrateTenant();
    check($baseline === MigrationTest::legacySnapshot(), 'Recovery migration changed old fields');
    output(['case' => 'backfill-failure-recovery', 'injected_trigger_blocks_category_update' => true, 'mysql_partial_ddl_retained_discard_only' => $databases['failed'], 'failed_migration_not_recorded' => true, 'legacy_business_fields_unchanged' => true, 'separate_restored_database_migrates' => $databases['recovery'], 'result' => 'pass']);
    unlink($cnf);
    output(['case' => 'retention', 'databases' => $databases, 'policy' => 'retained synthetic S008 databases; never reset/dropped; failed and sqlfail are discard-only', 'private_runtime' => $runtime, 'credential_file_removed' => ! file_exists($cnf), 'production_connected' => false, 'result' => 'pass']);
} catch (Throwable $exception) {
    file_put_contents($runtime.'/private-error.log', (string) $exception);
    chmod($runtime.'/private-error.log', 0600);
    output(['result' => 'fail', 'stage' => $stage, 'exception' => get_class($exception), 'private_diagnostic' => $runtime.'/private-error.log']);
    exit(1);
}
