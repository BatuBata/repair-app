<?php

namespace Tests\Feature\Users;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentAdminTest extends TestCase
{
    public static function operations(): array
    {
        return [['deactivate', 'deactivate'], ['demote', 'demote'], ['deactivate', 'demote'], ['demote', 'deactivate']];
    }

    #[DataProvider('operations')]
    public function test_concurrent_mutations_preserve_an_active_admin(string $first, string $second): void
    {
        $root = sys_get_temp_dir().'/t004-race-'.bin2hex(random_bytes(8));
        mkdir($root, 0700);
        $database = $root.'/database.sqlite';
        touch($database);
        config(['database.connections.t004_race' => ['driver' => 'sqlite', 'database' => $database, 'foreign_key_constraints' => true, 'busy_timeout' => 10000]]);
        $this->assertSame(0, Artisan::call('migrate', ['--database' => 't004_race', '--force' => true]));
        $connection = DB::connection('t004_race');
        foreach ([1, 2] as $id) {
            $connection->table('users')->insert(['id' => $id, 'name' => 'Admin '.$id, 'username' => 'admin'.$id, 'password' => Hash::make(bin2hex(random_bytes(16))), 'role' => 'admin', 'is_active' => true]);
        }
        $processes = [];
        foreach ([$first, $second] as $slot => $operation) {
            // Kedua koneksi mulai bersama dan mencoba menghilangkan dua admin yang berbeda.
            $process = new Process([PHP_BINARY, __DIR__.'/concurrency-worker.php', '1', (string) ($slot + 1), $operation, $root, (string) $slot], base_path(), ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array']);
            $process->setTimeout(15);
            $process->start();
            $processes[] = $process;
        }
        $deadline = microtime(true) + 10;
        while (! file_exists($root.'/ready-0') || ! file_exists($root.'/ready-1')) {
            if (microtime(true) > $deadline) {
                $this->fail('Concurrent workers did not reach the barrier.');
            }
            usleep(1000);
        }
        touch($root.'/start');
        $results = [];
        foreach ($processes as $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
            $results[] = $process->getOutput();
        }
        $this->assertContains('succeeded', $results);
        $this->assertCount(1, array_filter($results, fn ($result) => $result === 'succeeded'));
        $this->assertSame(1, $connection->table('users')->where('role', 'admin')->where('is_active', true)->count());
        DB::purge('t004_race');
        foreach (glob($root.'/*') as $file) {
            unlink($file);
        }
        rmdir($root);
    }
}
