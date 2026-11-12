<?php

namespace Tests\Feature\Domain;

use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SeederCacheTest extends TestCase
{
    public function test_seed_uses_cached_configuration_without_password_in_process_environment(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'repair-t002-db-');
        $cache = tempnam(sys_get_temp_dir(), 'repair-t002-config-');
        unlink($cache);
        $syntheticPassword = bin2hex(random_bytes(20));
        $environment = [
            'APP_ENV' => 'testing',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_CONFIG_CACHE' => $cache,
            'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '',
            'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'ADMIN_INITIAL_PASSWORD' => $syntheticPassword,
        ];
        try {
            $build = new Process([PHP_BINARY, 'artisan', 'config:cache'], base_path(), $environment);
            $build->run();
            $this->assertTrue($build->isSuccessful(), 'Synthetic config cache build failed.');
            $this->assertFileExists($cache);
            $seed = new Process([PHP_BINARY, 'artisan', 'migrate', '--seed', '--force'], base_path(), [...$environment, 'ADMIN_INITIAL_PASSWORD' => false]);
            $seed->run();
            $this->assertTrue($seed->isSuccessful(), 'Synthetic cached-config migration/seed failed.');
            $pdo = new PDO('sqlite:'.$database);
            $hash = $pdo->query("SELECT password FROM users WHERE username = 'admin'")->fetchColumn();
            $this->assertTrue(password_verify($syntheticPassword, $hash));
            $this->assertSame(1, $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn());
            $this->assertSame(9, $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn());
            $this->assertSame(30, $pdo->query('SELECT default_warranty_days FROM store_settings')->fetchColumn());
        } finally {
            // Only files created above for this synthetic fixture are removed.
            if (is_file($cache)) {
                unlink($cache);
            }
            unlink($database);
        }
    }
}
