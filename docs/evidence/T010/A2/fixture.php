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
    echo "Private config prepared.\n";
} elseif ($argv[1] === 'seed') {
    if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $root.'/fixture.sqlite') {
        throw new RuntimeException('Private SQLite required.');
    }
    Artisan::call('migrate', ['--force' => true]);
    $admin = User::factory()->create(['name' => 'Pak Arif', 'username' => 't010_a2_admin', 'role' => 'admin', 'password' => getenv('QA_PASSWORD')]);
    $tech = User::factory()->create(['name' => 'Rizky', 'username' => 't010_a2_tech', 'role' => 'teknisi', 'password' => getenv('QA_PASSWORD')]);
    $category = Category::create(['name' => 'Laptop']);
    StoreSetting::create(['name' => 'Anugerah', 'address' => '', 'phone' => '', 'default_warranty_days' => 30]);
    auth()->login($admin);
    $action = app(ManageServiceOrder::class);
    $ids = [];
    foreach (['working', 'negative', 'published-normal'] as $kind) {
        $o = app(CreateServiceOrder::class)->create(['customer_name' => 'Pak Hendra Wijaya', 'customer_phone' => '081347201186', 'category_id' => $category->id, 'complaint' => 'LCD bergaris, kadang mati sendiri', 'item_description' => 'Asus X441, hitam'], $tech);
        $action->assign($o->id, $tech->id);
        if ($kind !== 'working') {
            $row = $kind === 'negative' ? ['type' => 'part', 'description' => str_repeat('Deskripsi perangkat elektronik sintetis ', 6), 'qty' => 2147483648, 'price' => 0, 'cost' => 4294967295] : ['type' => 'part', 'description' => 'LCD 14 inci HD', 'qty' => 1, 'price' => 1000000, 'cost' => 700000];
            $action->invoice($o->id, ['rows' => [$row], 'warranty_days' => 30, 'unrepairable' => false], false, InvoiceLines::revision($o->fresh()));
        }
        $ids[$kind] = $o->id;
    }
    file_put_contents($root.'/ids.json', json_encode($ids));
    chmod($root.'/ids.json', 0600);
    echo json_encode(['private_sqlite' => true, 'orders' => ServiceOrder::count(), 'private_views' => str_starts_with(config('view.compiled'), $root), 'private_storage' => storage_path() === $root.'/storage'])."\n";
} elseif ($argv[1] === 'grand') {
    auth()->login(User::where('username', 't010_a2_admin')->firstOrFail());
    $o = app(CreateServiceOrder::class)->create(['customer_name' => 'Pak Hendra Wijaya', 'customer_phone' => '081347201186', 'category_id' => Category::firstOrFail()->id, 'complaint' => 'LCD bergaris'], User::where('username', 't010_a2_tech')->firstOrFail());
    app(ManageServiceOrder::class)->assign($o->id, User::where('username', 't010_a2_tech')->firstOrFail()->id);
    $ids = json_decode(file_get_contents($root.'/ids.json'), true);
    $ids['grand-working'] = $o->id;
    file_put_contents($root.'/ids.json', json_encode($ids));
    echo "Private grand-only working order prepared.\n";
} elseif ($argv[1] === 'proof') {
    $proof = ServiceOrder::with('items')->get()->map(fn ($o) => ['id' => $o->id, 'status' => $o->status->value, 'invoiced_on' => $o->invoiced_on?->toDateString(), 'rows' => $o->items->map(fn ($i) => ['description' => $i->description, 'qty' => $i->qty, 'price' => $i->price, 'cost' => $i->cost]), 'totals_decimal' => array_map(fn ($v) => (string) $v, array_intersect_key(InvoiceLines::calculate($o->items->toArray()), array_flip(['price', 'cost', 'profit'])))]);
    echo $proof->toJson()."\n";
} else {
    throw new RuntimeException('Unknown mode.');
}
