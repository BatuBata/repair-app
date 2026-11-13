<?php

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require getcwd().'/vendor/autoload.php';
$root = getenv('QA_RUNTIME');
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($root.'/storage');
$app->make(Kernel::class)->bootstrap();
if ($argv[1] === 'config') {
    file_put_contents(getenv('APP_CONFIG_CACHE'), '<?php return '.var_export(config()->all(), true).';');
    chmod(getenv('APP_CONFIG_CACHE'), 0600);
    echo json_encode(['view_private' => str_starts_with(config('view.compiled'), $root), 'session_private' => str_starts_with(config('session.files'), $root), 'storage_private' => storage_path() === $root.'/storage'])."\n";
} elseif ($argv[1] === 'seed') {
    if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $root.'/fixture.sqlite') {
        throw new RuntimeException('Private SQLite required');
    }
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Migration failed');
    }
    $admin = User::factory()->create(['name' => 'Admin QA T016 A2', 'username' => 't016a2_admin', 'role' => 'admin', 'password' => getenv('QA_PASSWORD')]);
    $tech = User::factory()->create(['name' => 'Teknisi QA', 'username' => 't016a2_tech', 'role' => 'teknisi', 'password' => getenv('QA_PASSWORD')]);
    $cat = Category::create(['name' => 'Laptop']);
    StoreSetting::create(['name' => 'Anugerah', 'address' => '', 'phone' => '', 'default_warranty_days' => 30]);
    auth()->login($admin);
    foreach (['normal', 'large', 'negative', 'probe14', 'probe15', 'probe19'] as $kind) {
        $o = app(CreateServiceOrder::class)->create(['customer_name' => 'Pelanggan QA A2', 'customer_phone' => '081234567890', 'category_id' => $cat->id, 'complaint' => 'LCD bergaris, kadang mati sendiri', 'item_description' => 'Perangkat sintetis'], $tech);
        app(ManageServiceOrder::class)->assign($o->id, $tech->id);
        $ids[$kind] = $o->id;
    }
    file_put_contents($root.'/ids.json', json_encode($ids));
    chmod($root.'/ids.json', 0600);
    echo json_encode(['private_sqlite' => true, 'orders' => ServiceOrder::count()])."\n";
} elseif ($argv[1] === 'proof') {
    echo ServiceOrder::with('items')->get()->map(fn ($o) => ['id' => $o->id, 'status' => $o->status->value, 'invoice_date' => $o->invoiced_on?->toDateString(), 'picked_date' => $o->picked_up_on?->toDateString(), 'rows' => $o->items->map(fn ($i) => ['description' => $i->description, 'description_length' => strlen($i->description), 'qty' => (string) $i->qty, 'price' => (string) $i->price, 'cost' => (string) $i->cost]), 'totals_decimal' => array_map(fn ($v) => (string) $v, array_intersect_key(InvoiceLines::calculate($o->items->toArray()), array_flip(['price', 'cost', 'profit'])))])->toJson()."\n";
}
