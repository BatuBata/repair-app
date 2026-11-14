<?php
ini_set('display_errors', '0');
$phase = 'guard';
$temporary = null;
try {
    $payload = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($payload) || !is_string($payload['password'] ?? null) || !preg_match('/\A[0-9a-f]{40}\z/', $payload['revision'] ?? '')) throw new RuntimeException('Invalid input');
    $root = '/var/www/demo-repair-app/current';
    if (trim(file_get_contents($root.'/.release-revision')) !== $payload['revision']) throw new RuntimeException('Wrong deployed source');
    chdir($root);
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if (!app()->isProduction() || config('app.debug') || config('app.url') !== 'https://demo-repair-app.scannara.asia' || config('database.connections.'.config('database.default').'.database') !== 'demo_repair_app' || config('database.connections.'.config('database.default').'.username') !== 'new_gym') throw new RuntimeException('Wrong target');
    Illuminate\Support\Facades\Validator::make(['password'=>$payload['password']], ['password'=>['required','string',Illuminate\Validation\Rules\Password::default()]])->validate();
    $handover = '/home/muzib/deploy-demo-repair-app/initial-admin-password.txt';
    if (!is_file($handover) || is_link($handover) || (fileperms($handover) & 0777) !== 0600 || (fileperms(dirname($handover)) & 0777) !== 0700) throw new RuntimeException('Private handover guard');
    $temporary = tempnam(dirname($handover), '.admin-password-');
    if ($temporary === false || !chmod($temporary, 0600) || file_put_contents($temporary, $payload['password'].PHP_EOL) === false) throw new RuntimeException('Private write failed');
    $phase = 'single-admin-update';
    Illuminate\Support\Facades\DB::transaction(function () use ($payload) {
        $admins = App\Models\User::where('username', 'admin')->where('role', 'admin')->where('is_active', true)->lockForUpdate()->get();
        if ($admins->count() !== 1) throw new RuntimeException('Expected exactly one active admin');
        $admin = $admins->first();
        $before = $admin->getAttributes();
        $others = Illuminate\Support\Facades\DB::table('users')->where('id', '<>', $admin->id)->orderBy('id')->get()->toJson();
        $admin->forceFill(['password'=>$payload['password'], 'remember_token'=>Illuminate\Support\Str::random(60)])->save();
        $admin->refresh();
        $after = $admin->getAttributes();
        if (!Illuminate\Support\Facades\Hash::check($payload['password'], $admin->password) || $before['remember_token'] === $after['remember_token']) throw new RuntimeException('Credential verification failed');
        foreach (['password', 'remember_token', 'updated_at'] as $key) { unset($before[$key], $after[$key]); }
        if ($before !== $after || $others !== Illuminate\Support\Facades\DB::table('users')->where('id', '<>', $admin->id)->orderBy('id')->get()->toJson()) throw new RuntimeException('Unexpected account change');
    });
    $phase = 'private-handover-update';
    if (!rename($temporary, $handover)) throw new RuntimeException('Private handover sync failed');
    $temporary = null;
    if (file_get_contents($handover) !== $payload['password'].PHP_EOL || (fileperms($handover) & 0777) !== 0600) throw new RuntimeException('Private handover verification failed');
    echo json_encode(['status'=>'PASS','revision'=>$payload['revision'],'username'=>'admin','active_admin_credential_changed'=>true,'password_hash_verified'=>true,'remember_token_rotated'=>true,'other_accounts_unchanged'=>true,'noncredential_admin_fields_unchanged'=>true,'private_handover_updated'=>true,'private_handover_mode'=>'0600','secret_output'=>false]), PHP_EOL;
} catch (Throwable $error) {
    if (is_string($temporary) && is_file($temporary)) unlink($temporary);
    echo json_encode(['status'=>'FAIL','phase'=>$phase,'exception_class'=>get_class($error),'message'=>'suppressed','secret_output'=>false]), PHP_EOL;
    exit(1);
}
