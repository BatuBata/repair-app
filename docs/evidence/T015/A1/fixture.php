<?php

use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require getcwd().'/vendor/autoload.php';
$runtime = getenv('T015_RUNTIME');
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($runtime.'/storage');
$app->make(Kernel::class)->bootstrap();
if ($argv[1] === 'config') {
    file_put_contents(getenv('APP_CONFIG_CACHE'), '<?php return '.var_export(config()->all(), true).';');
    chmod(getenv('APP_CONFIG_CACHE'), 0600);
    echo "Private config cache prepared.\n";
} elseif ($argv[1] === 'seed') {
    $code = Artisan::call('migrate', ['--force' => true]);
    if ($code !== 0) {
        throw new RuntimeException('Private migration failed.');
    }
    foreach (['admin', 'tech'] as $role) {
        User::factory()->create(['name' => $role === 'admin' ? 'Admin Integrasi' : 'Rizky Uji', 'username' => 't015_'.$role, 'role' => $role === 'admin' ? 'admin' : 'teknisi', 'password' => getenv('QA_PASSWORD')]);
    }
    Category::create(['name' => 'Laptop']);
    StoreSetting::create(['name' => 'Anugerah', 'address' => 'Alamat toko sintetis', 'phone' => '081234567890', 'default_warranty_days' => 30]);
    echo json_encode(['driver' => config('database.default'), 'new_private_database' => true, 'migrations_exit' => $code, 'users' => User::count(), 'categories' => Category::count(), 'private_storage' => str_starts_with(config('view.compiled'), $runtime) && str_starts_with(config('session.files'), $runtime)])."\n";
} elseif ($argv[1] === 'proof') {
    $order = ServiceOrder::query()->latest('id')->first();
    if ($order) {
        file_put_contents($runtime.'/tracking.json', json_encode(['path' => '/t/'.$order->tracking_token]));
        chmod($runtime.'/tracking.json', 0600);
    }
    echo json_encode([
        'orders' => ServiceOrder::with('items')->get()->map(fn ($order) => ['id' => $order->id, 'status' => $order->status->value, 'invoiced_on' => $order->invoiced_on?->toDateString(), 'total' => $order->items->sum(fn ($line) => $line->qty * $line->price), 'cost' => $order->items->sum(fn ($line) => $line->qty * $line->cost)])->all(),
        'products' => Product::query()->get(['id', 'name', 'stock'])->toArray(),
        'sales' => Sale::with('items')->get()->map(fn ($sale) => ['id' => $sale->id, 'items' => $sale->items->map(fn ($item) => ['qty' => $item->qty, 'price' => $item->price, 'cost' => $item->cost])->all()])->all(), 'expenses' => Expense::query()->get(['id', 'amount'])->toArray(),
    ])."\n";
} else {
    throw new RuntimeException('Unknown fixture mode.');
}
