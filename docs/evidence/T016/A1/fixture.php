<?php

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
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
    echo json_encode(['private_view' => str_starts_with(config('view.compiled'), $root), 'private_session' => str_starts_with(config('session.files'), $root)])."\n";
} elseif ($argv[1] === 'seed') {
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        throw new RuntimeException('Migration failed');
    }
    $admin = User::factory()->create(['username' => 't016_admin', 'name' => 'Admin QA T016', 'role' => 'admin', 'password' => getenv('QA_PASSWORD')]);
    $tech = User::factory()->create(['username' => 't016_tech', 'name' => 'Teknisi QA T016', 'role' => 'teknisi', 'password' => getenv('QA_PASSWORD')]);
    $cat = Category::create(['name' => 'Laptop']);
    StoreSetting::create(['name' => 'Anugerah', 'address' => 'Alamat sintetis toko', 'phone' => '081234567890', 'default_warranty_days' => 30]);
    auth()->login($admin);
    $order = app(CreateServiceOrder::class)->create(['customer_name' => 'Pelanggan Uji T016', 'customer_phone' => '0812-3456-7890', 'customer_address' => 'Alamat privat sintetis', 'category_id' => $cat->id, 'complaint' => 'LCD bergaris', 'item_description' => 'Perangkat sintetis'], $tech);
    app(ManageServiceOrder::class)->assign($order->id, $tech->id);
    echo json_encode(['new_private_sqlite' => true, 'order_id' => $order->id, 'status' => $order->fresh()->status->value])."\n";
} elseif ($argv[1] === 'large') {
    auth()->login(User::where('username', 't016_admin')->firstOrFail());
    $order = ServiceOrder::firstOrFail();
    $rows = [['type' => 'part', 'description' => str_repeat('Deskripsi panjang perangkat elektronik sintetis ', 5), 'qty' => 2147483647, 'price' => 4294967295, 'cost' => 4294967295], ['type' => 'part', 'description' => 'Tambahan batas integer', 'qty' => 1, 'price' => 4294967295, 'cost' => 0], ['type' => 'jasa', 'description' => 'Jasa pemeriksaan', 'qty' => 1, 'price' => 2147483647, 'cost' => 5000]];
    app(ManageServiceOrder::class)->invoice($order->id, ['rows' => $rows, 'warranty_days' => 30, 'unrepairable' => false], false, InvoiceLines::revision($order));
    echo json_encode(['total' => InvoiceLines::calculate($order->items()->get()->toArray())['price'], 'invoice_date' => $order->fresh()->invoiced_on->toDateString()])."\n";
} elseif ($argv[1] === 'negative') {
    auth()->login(User::where('username', 't016_admin')->firstOrFail());
    $o = app(CreateServiceOrder::class)->create(['customer_name' => 'Pelanggan negatif QA', 'customer_phone' => '081234567890', 'category_id' => Category::firstOrFail()->id, 'complaint' => 'Skenario laba negatif sintetis'], User::where('username', 't016_tech')->firstOrFail());
    app(ManageServiceOrder::class)->assign($o->id, User::where('username', 't016_tech')->firstOrFail()->id);
    app(ManageServiceOrder::class)->invoice($o->id, ['rows' => [['type' => 'part', 'description' => 'Part besar sintetis tanpa biaya customer', 'qty' => 2147483648, 'price' => 0, 'cost' => 4294967295]], 'warranty_days' => 0, 'unrepairable' => true], false, InvoiceLines::revision($o->fresh()));
    echo json_encode(['order' => $o->id, 'totals' => InvoiceLines::calculate($o->items()->get()->toArray())])."\n";
} elseif ($argv[1] === 'proof') {
    $order = ServiceOrder::latest('id')->first();
    file_put_contents($root.'/tracking.json', json_encode(['path' => '/t/'.$order->tracking_token]));
    chmod($root.'/tracking.json', 0600);
    echo json_encode(['orders' => ServiceOrder::with('items')->get()->map(fn ($o) => ['id' => $o->id, 'status' => $o->status->value, 'normalized_phone' => $o->customer_phone === '6281234567890', 'invoice_date' => $o->invoiced_on?->toDateString(), 'picked_date' => $o->picked_up_on?->toDateString(), 'warranty_until' => $o->warranty_until?->toDateString(), 'items' => $o->items->map(fn ($line) => $line->only(['type', 'description', 'qty', 'price', 'cost']))]), 'products' => Product::get(['id', 'name', 'stock', 'cost_price', 'sell_price']), 'sales' => Sale::with('items')->get()->map(fn ($s) => ['id' => $s->id, 'number' => $s->number, 'warranty_until' => $s->warranty_until?->toDateString(), 'items' => $s->items->map(fn ($line) => $line->only(['product_name', 'qty', 'price', 'cost']))]), 'expenses' => Expense::get(['id', 'amount'])])."\n";
}
