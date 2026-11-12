<?php

use App\Models\Sale;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Services\DocumentNumber;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::connection()->getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'repair_app_test') {
    throw new RuntimeException('Only repair_app_test on MySQL is permitted.');
}
$prefix = $argv[1];
$start = (float) $argv[2];
CarbonImmutable::setTestNow(new CarbonImmutable('2026-11-12 12:00:00 Asia/Makassar'));
while (microtime(true) < $start) {
    usleep(1000);
}
$entered = microtime(true);
$attempts = 0;
$lastAttemptEntered = 0;
$number = DB::transaction(function () use ($prefix, &$attempts, &$lastAttemptEntered) {
    $attempts++;
    $lastAttemptEntered = microtime(true);
    if ($prefix === 'TTS') {
        $number = app(CreateServiceOrder::class)->create([
            'customer_name' => 'Concurrent synthetic', 'customer_phone' => '081234567890',
            'category_id' => 1, 'complaint' => 'Synthetic test',
        ], User::where('username', 'admin')->firstOrFail())->number;
    } else {
        $number = app(DocumentNumber::class)->next('JL');
        Sale::create(['number' => $number, 'sold_on' => '2026-11-12', 'created_by' => User::where('username', 'admin')->firstOrFail()->id]);
    }
    usleep(150000);

    return $number;
}, 5);
echo json_encode(['number' => $number, 'entered' => $entered, 'attempts' => $attempts, 'last_attempt_entered' => $lastAttemptEntered, 'finished' => microtime(true)], JSON_THROW_ON_ERROR);
