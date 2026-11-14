<?php

namespace Tests\Feature\Domain;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class MySqlConcurrencyTest extends TestCase
{
    public function test_mysql_fresh_seed_and_parallel_document_creation(): void
    {
        if (getenv('RUN_MYSQL_DOMAIN') !== '1') {
            $this->markTestSkipped('BLOCKED: MySQL acceptance requires RUN_MYSQL_DOMAIN=1 and local credentials; SQLite is not concurrency evidence.');
        }
        $envFile = getenv('REPAIR_MYSQL_ENV_FILE');
        $local = $envFile ? Dotenv::parse(file_get_contents($envFile)) : [];
        $username = $local['DB_USERNAME'] ?? getenv('DB_USERNAME');
        $password = $local['ADMIN_INITIAL_PASSWORD'] ?? getenv('ADMIN_INITIAL_PASSWORD');
        $this->assertNotEmpty($username, 'Configure MySQL credentials locally.');
        $this->assertNotEmpty($password, 'Configure ADMIN_INITIAL_PASSWORD locally.');
        $environment = [
            'APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_URL' => '',
            'DB_DATABASE' => 'repair_app_test',
            'DB_HOST' => $local['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
            'DB_PORT' => $local['DB_PORT'] ?? getenv('DB_PORT') ?: '3306',
            'DB_USERNAME' => $username, 'DB_PASSWORD' => $local['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
        ];
        $this->assertContains($environment['DB_HOST'], ['127.0.0.1', 'localhost', '::1'], 'Only local MySQL is permitted.');
        config(['database.default' => 'mysql', 'database.connections.mysql.url' => null]);
        foreach (['host', 'port', 'database', 'username', 'password'] as $key) {
            config(['database.connections.mysql.'.$key => $environment['DB_'.strtoupper($key)]]);
        }
        DB::purge('mysql');
        $this->assertSame('repair_app_test', DB::connection()->getDatabaseName());
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertCount(0, Schema::getTables(DB::connection()->getDatabaseName()), 'STOP: test database must be empty; no existing data will be dropped.');
        $this->assertCount(0, Schema::getViews(DB::connection()->getDatabaseName()), 'STOP: test database must have no existing views.');
        $oldPassword = config('repair.admin_initial_password');
        config(['repair.admin_initial_password' => $password]);
        try {
            $this->artisan('migrate:fresh', ['--seed' => true, '--force' => true])->assertExitCode(0);
        } finally {
            config(['repair.admin_initial_password' => $oldPassword]);
        }
        foreach (['TTS', 'JL'] as $prefix) {
            // Round one creates the counter row concurrently; round two locks an existing row.
            foreach ([0, 4] as $offset) {
                $start = (string) (microtime(true) + 2);
                $processes = [];
                for ($i = 0; $i < 4; $i++) {
                    $process = new Process([PHP_BINARY, __DIR__.'/mysql-worker.php', $prefix, $start], base_path(), $environment);
                    $process->setTimeout(20);
                    $process->start();
                    $processes[] = $process;
                }
                $actual = [];
                $timings = [];
                $entered = [];
                $finished = [];
                foreach ($processes as $process) {
                    $process->wait();
                    $this->assertTrue($process->isSuccessful(), 'MySQL child failed (inspect local process; credentials are not logged).');
                    $result = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
                    $actual[] = $result['number'];
                    $entered[] = $result['entered'];
                    $finished[] = $result['finished'];
                    $timings[] = [
                        'entered' => $result['entered'], 'attempts' => $result['attempts'],
                        'last_attempt_entered' => $result['last_attempt_entered'], 'finished' => $result['finished'],
                    ];
                }
                if ($diagnostics = getenv('T002_MYSQL_DIAGNOSTICS')) {
                    file_put_contents($diagnostics, json_encode(['prefix' => $prefix, 'offset' => $offset, 'workers' => $timings], JSON_THROW_ON_ERROR).PHP_EOL, FILE_APPEND);
                }
                $this->assertLessThan(min($finished), max($entered), 'All four transaction attempts must overlap.');
                sort($actual);
                $expected = array_map(fn ($n) => $prefix.'-2611-'.str_pad((string) $n, 3, '0', STR_PAD_LEFT), range($offset + 1, $offset + 4));
                $this->assertSame($expected, $actual);
            }
        }
        $this->assertSame(8, DB::table('service_orders')->count());
        $this->assertSame(8, DB::table('sales')->count());
        $this->assertSame(8, DB::table('service_orders')->distinct()->count('tracking_token'));
        $columns = collect(Schema::getColumns('service_order_items'))->keyBy('name');
        foreach (['price', 'cost'] as $column) {
            $this->assertSame('int', $columns[$column]['type_name']);
            $this->assertStringContainsString('unsigned', $columns[$column]['type']);
        }
        foreach (['status', 'invoiced_on', 'warranty_until', 'customer_phone'] as $column) {
            $this->assertTrue(Schema::hasIndex('service_orders', [$column]));
        }
    }
}
