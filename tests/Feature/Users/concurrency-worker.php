<?php

use App\Livewire\Users\Index;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
[$script, $actor, $target, $operation, $barrier, $slot] = $argv;
auth()->loginUsingId($actor);
app(TenantContext::class)->set(Tenant::findOrFail(auth()->user()->tenant_id));
$component = new Index;
touch($barrier.'/ready-'.$slot);
$deadline = microtime(true) + 10;
while (! file_exists($barrier.'/start')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Barrier timeout');
    }
    usleep(1000);
}
try {
    if ($operation === 'deactivate') {
        $component->toggleActive($target);
    } else {
        $component->edit($target);
        $component->role = 'teknisi';
        $component->save();
    }
    echo 'succeeded';
} catch (ValidationException) {
    echo 'protected';
} catch (HttpException $exception) {
    if ($exception->getStatusCode() !== 403) {
        throw $exception;
    }
    echo 'forbidden';
}
