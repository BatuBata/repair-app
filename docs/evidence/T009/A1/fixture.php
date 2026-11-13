<?php

use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require getcwd().'/vendor/autoload.php';
$runtime = getenv('T009_RUNTIME');
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($runtime.'/storage');
$app->make(Kernel::class)->bootstrap();
if ($argv[1] === 'config') {
    file_put_contents(getenv('APP_CONFIG_CACHE'), '<?php return '.var_export(config()->all(), true).';');
    chmod(getenv('APP_CONFIG_CACHE'), 0600);
    echo "Private config cache prepared.\n";
} elseif ($argv[1] === 'seed') {
    Artisan::call('migrate', ['--seed' => true, '--force' => true]);
    $admin = User::where('username', 'admin')->firstOrFail();
    $actor = User::factory()->create(['username' => 't009_teknisi', 'name' => 'Rizky Uji', 'role' => 'teknisi', 'password' => getenv('ADMIN_INITIAL_PASSWORD')]);
    $peer = User::factory()->create(['username' => 't009_peer', 'name' => 'Dimas Uji', 'role' => 'teknisi', 'password' => getenv('ADMIN_INITIAL_PASSWORD')]);
    foreach (['Bu Ratna Sari', 'Pak Hendra Wijaya', 'Ibu Siti', 'Pak Adi', str_repeat('Nama panjang ', 6)] as $i => $name) {
        $order = app(CreateServiceOrder::class)->create(['customer_name' => $name, 'customer_phone' => '081255312097', 'category_id' => Category::firstOrFail()->id, 'complaint' => 'Pengering tidak berputar', 'item_description' => 'Sharp 2 tabung, 8 kg', 'customer_address' => 'Jl. A. Yani Km 4, Gg. Melati 2'], $i === 0 ? $actor : $peer);
        if ($i === 1) {
            $order->update(['status' => 'dikerjakan', 'technician_id' => $actor->id, 'assigned_at' => now()]);
        }
        $order->items()->create(['type' => 'part', 'description' => 'PART PRIVAT UJI', 'qty' => 1, 'price' => 919191, 'cost' => 818181]);
    }
    echo json_encode(['driver' => config('database.default'), 'users' => User::count(), 'orders' => ServiceOrder::count(), 'categories' => Category::count(), 'private_views' => str_starts_with(config('view.compiled'), $runtime), 'private_local_disk' => str_starts_with(config('filesystems.disks.local.root'), $runtime)])."\n";
} else {
    throw new RuntimeException('Unknown fixture mode.');
}
