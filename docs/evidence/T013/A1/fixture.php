<?php

use App\Actions\Sales\CreateSale;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (($argv[1] ?? '') === 'init') {
    User::factory()->create(['username' => 't013_admin', 'name' => 'Admin Sintetis T013', 'role' => 'admin', 'password' => getenv('QA_PASSWORD')]);
    User::factory()->create(['username' => 't013_teknisi', 'name' => 'Teknisi Sintetis T013', 'password' => getenv('QA_PASSWORD')]);
    StoreSetting::create(['id' => 1, 'name' => 'Anugerah QA T013', 'address' => 'Alamat sintetis', 'phone' => '081234567890', 'default_warranty_days' => 30]);
    Product::create(['name' => 'Kabel HDMI dua meter dengan konektor berlapis dan pelindung kabel untuk perangkat televisi dan komputer', 'cost_price' => 100000, 'sell_price' => 125000, 'stock' => 3]);
    Product::create(['name' => 'Remote universal', 'cost_price' => 25000, 'sell_price' => 40000, 'stock' => 5]);
    Product::create(['name' => 'Barang nonaktif', 'cost_price' => 12000, 'sell_price' => 20000, 'stock' => 5, 'is_active' => false]);
    echo "Private synthetic users/store/products ready.\n";
} elseif (($argv[1] ?? '') === 'visual-sale') {
    auth()->login(User::where('username', 't013_admin')->firstOrFail());
    $sale = app(CreateSale::class)->handle(['sold_on' => '2026-11-13', 'customer_name' => 'Ibu Ratna Sari pelanggan perangkat elektronik rumah tangga di lingkungan Balikpapan', 'customer_phone' => '081234567890', 'warranty_days' => '30', 'items' => [['product_id' => '1', 'qty' => '2', 'price' => '150000']]]);
    echo json_encode(['fixture_sale_id' => $sale->id, 'actual_action' => CreateSale::class])."\n";
} elseif (($argv[1] ?? '') === 'visual-large') {
    auth()->login(User::where('username', 't013_admin')->firstOrFail());
    $product = Product::create(['name' => 'Batas nominal integer besar T013', 'cost_price' => 4294967295, 'sell_price' => 4294967295, 'stock' => 2147483648]);
    $sale = app(CreateSale::class)->handle(['sold_on' => '2026-11-13', 'warranty_days' => '0', 'items' => [['product_id' => (string) $product->id, 'qty' => '2147483648', 'price' => '4294967295']]]);
    echo json_encode(['fixture_sale_id' => $sale->id, 'actual_action' => CreateSale::class, 'integer_total' => $sale->items()->firstOrFail()->subtotalPrice()])."\n";
} elseif (($argv[1] ?? '') === 'proof') {
    $sales = Sale::with('items')->get();
    echo json_encode(['sales' => $sales->map(fn ($sale) => ['number' => $sale->number, 'customer_name' => $sale->customer_name, 'customer_phone' => $sale->customer_phone, 'sold_on' => $sale->sold_on->toDateString(), 'warranty_days' => $sale->warranty_days, 'warranty_until' => $sale->warranty_until?->toDateString(), 'items' => $sale->items->map(fn ($item) => $item->only(['product_name', 'qty', 'price', 'cost']))]), 'stocks' => Product::orderBy('id')->pluck('stock')->all(), 'items' => SaleItem::count(), 'expenses' => Expense::count(), 'private_session' => str_starts_with(config('session.files'), getenv('QA_RUNTIME')), 'private_view' => str_starts_with(config('view.compiled'), getenv('QA_RUNTIME'))], JSON_PRETTY_PRINT)."\n";
}
