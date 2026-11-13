<?php

use App\Actions\Sales\CreateSale;
use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require getcwd().'/vendor/autoload.php';
$root = getenv('QA_RUNTIME');
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($root.'/storage');
$app->make(Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $root.'/fixture.sqlite') {
    throw new RuntimeException('Only the new private SQLite fixture is allowed.');
}
if ($argv[1] === 'config') {
    file_put_contents(getenv('APP_CONFIG_CACHE'), '<?php return '.var_export(config()->all(), true).';');
    chmod(getenv('APP_CONFIG_CACHE'), 0600);
    echo "Private config prepared.\n";
} elseif ($argv[1] === 'seed') {
    Artisan::call('migrate', ['--force' => true]);
    User::factory()->create(['name' => 'Pak Arif', 'username' => 't018_admin', 'role' => 'admin', 'password' => getenv('QA_PASSWORD')]);
    User::factory()->create(['name' => 'Rizky', 'username' => 't018_tech', 'role' => 'teknisi', 'password' => getenv('QA_PASSWORD')]);
    Category::create(['name' => 'Laptop']);
    StoreSetting::create(['name' => 'Anugerah', 'address' => '', 'phone' => '', 'default_warranty_days' => 30]);
    echo json_encode(['empty_dashboard' => true, 'private_views' => str_starts_with(config('view.compiled'), $root), 'private_storage' => storage_path() === $root.'/storage', 'private_database' => true])."\n";
} elseif (in_array($argv[1], ['populate', 'long', 'negative'], true)) {
    auth()->login(User::where('username', 't018_admin')->firstOrFail());
    $tech = User::where('username', 't018_tech')->firstOrFail();
    $today = CarbonImmutable::now('Asia/Makassar')->startOfDay();
    $ids = is_file($root.'/ids.json') ? json_decode(file_get_contents($root.'/ids.json'), true) : [];
    $service = function (string $kind, int $ago, int $price, int $qty = 1, bool $long = false, bool $negative = false) use ($tech, $today) {
        CarbonImmutable::setTestNow($today->subDays($ago)->setTime(9, 0));
        $category = $long ? Category::firstOrCreate(['name' => str_repeat('Perangkat elektronik ', 12)]) : Category::firstOrFail();
        $o = app(CreateServiceOrder::class)->create(['customer_name' => $long ? str_repeat('Hendra Wijaya ', 7) : 'Bu Dewi Kartika', 'customer_phone' => '081234567890', 'category_id' => $category->id, 'complaint' => 'LCD bergaris', 'item_description' => $long ? str_repeat('Laptop Asus sintetis ', 12) : 'Asus X441'], $tech);
        if ($kind !== 'received') {
            app(ManageServiceOrder::class)->assign($o->id, $tech->id);
        }
        if ($kind === 'invoice') {
            app(ManageServiceOrder::class)->invoice($o->id, ['rows' => [['type' => 'part', 'description' => 'LCD 14 inci HD', 'qty' => $qty, 'price' => $price, 'cost' => $negative ? 4294967295 : ($long ? 0 : 700000)]], 'warranty_days' => $negative ? 0 : ($long ? 2 : 30), 'unrepairable' => false], false, InvoiceLines::revision($o->fresh()));
        }
        CarbonImmutable::setTestNow();

        return $o->id;
    };
    if ($argv[1] === 'populate') {
        $ids['received'] = $service('received', 0, 0);
        $ids['working'] = $service('working', 0, 0);
        $ids['service'] = $service('invoice', 29, 1000000);
        $ids['service-today'] = $service('invoice', 0, 1100000);
        $ids['service-lastday'] = $service('invoice', 30, 75000);
    } else {
        for ($i = 0; $i < 2; $i++) {
            $ids['large-service-'.$i] = $service('invoice', 0, $argv[1] === 'negative' ? 0 : 4294967295, 2147483648, true, $argv[1] === 'negative');
        }
    }
    for ($i = 0; $i < ($argv[1] !== 'populate' ? 2 : 1); $i++) {
        $long = $argv[1] !== 'populate';
        $negative = $argv[1] === 'negative';
        $product = Product::create(['name' => $long ? str_repeat('Printer Canon sintetis ', 10) : 'Printer bekas Canon', 'cost_price' => $negative ? 4294967295 : 0, 'sell_price' => $negative ? 0 : ($long ? 4294967295 : 250000), 'stock' => $long ? 2147483648 : 3]);
        $sale = app(CreateSale::class)->handle(['sold_on' => $today->toDateString(), 'customer_name' => $long ? str_repeat('Bu Ratna Sari ', 7) : 'Pak Joko Susilo', 'customer_phone' => '081234567890', 'warranty_days' => $negative ? 0 : ($long ? 2 : 3), 'items' => [['product_id' => $product->id, 'qty' => $long ? 2147483648 : 1, 'price' => $product->sell_price]]]);
        $ids[$long ? 'large-sale-'.$i : 'sale'] = $sale->id;
    }
    file_put_contents($root.'/ids.json', json_encode($ids));
    chmod($root.'/ids.json', 0600);
    echo json_encode(['mode' => $argv[1], 'service_count' => ServiceOrder::count(), 'sale_count' => Sale::count(), 'expected_daily_revenue' => $argv[1] === 'long' ? 'Rp36.893.488.138.830.518.640' : ($argv[1] === 'negative' ? 'Rp0' : 'Rp1.350.000')])."\n";
} elseif (in_array($argv[1], ['normal-money', 'large-money'], true)) {
    $ids = json_decode(file_get_contents($root.'/ids.json'), true);
    $day = CarbonImmutable::now('Asia/Makassar')->startOfDay();
    if ($argv[1] === 'normal-money') {
        $day = $day->subDay();
    }
    foreach ([0, 1] as $i) {
        ServiceOrder::findOrFail($ids['large-service-'.$i])->update(['invoiced_on' => $day->toDateString(), 'warranty_until' => $day->addDays(2)->toDateString()]);
        Sale::findOrFail($ids['large-sale-'.$i])->update(['sold_on' => $day->toDateString(), 'warranty_until' => $day->addDays(2)->toDateString()]);
    }
    echo "Own large fixture dates updated for KPI-only subset.\n";
} elseif ($argv[1] === 'stale') {
    $ids = json_decode(file_get_contents($root.'/ids.json'), true);
    Sale::findOrFail($ids['sale'])->update(['warranty_until' => CarbonImmutable::now('Asia/Makassar')->subDay()->toDateString()]);
    echo "Own sale warranty expired after browser load.\n";
} elseif ($argv[1] === 'proof') {
    echo json_encode(['services' => ServiceOrder::get(['id', 'status', 'warranty_contacted_at'])->toArray(), 'sales' => Sale::get(['id', 'warranty_contacted_at'])->toArray()])."\n";
} else {
    throw new RuntimeException('Unknown fixture mode.');
}
