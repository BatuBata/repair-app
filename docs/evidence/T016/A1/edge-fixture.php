<?php

use App\Actions\Sales\CreateSale;
use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Illuminate\Contracts\Console\Kernel;

require getcwd().'/vendor/autoload.php';
$root = getenv('QA_RUNTIME');
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($root.'/storage');
$app->make(Kernel::class)->bootstrap();
$admin = User::where('username', 't016_admin')->firstOrFail();
$tech = User::where('username', 't016_tech')->firstOrFail();
auth()->login($admin);
$action = app(ManageServiceOrder::class);
$paths = [];
foreach (['received', 'working', 'cancelled', 'zero', 'large'] as $mode) {
    $o = app(CreateServiceOrder::class)->create(['customer_name' => 'Pelanggan edge QA', 'customer_phone' => '081234567890', 'customer_address' => 'Alamat privat edge QA', 'category_id' => Category::firstOrFail()->id, 'complaint' => 'Keluhan sintetis edge '.$mode], $tech);
    if ($mode !== 'received') {
        $action->assign($o->id, $tech->id);
    }
    if ($mode === 'cancelled') {
        $action->cancel($o->id, 'Pembatalan sintetis edge');
    }
    if (in_array($mode, ['zero', 'large'], true)) {
        $row = $mode === 'zero' ? ['type' => 'jasa', 'description' => 'Biaya cek gratis', 'qty' => 1, 'price' => 0, 'cost' => 5000] : ['type' => 'part', 'description' => str_repeat('Deskripsi perangkat elektronik sintetis ', 6), 'qty' => 2147483648, 'price' => 4294967295, 'cost' => 4294967295];
        $action->invoice($o->id, ['rows' => [$row], 'warranty_days' => 0, 'unrepairable' => true], false, InvoiceLines::revision($o->fresh()));
    }
    $paths[$mode] = '/t/'.$o->tracking_token;
}
$p = Product::create(['name' => str_repeat('Barang panjang sintetis QA ', 9), 'cost_price' => 4294967295, 'sell_price' => 4294967295, 'stock' => 2147483648]);
$s = app(CreateSale::class)->handle(['sold_on' => now('Asia/Makassar')->toDateString(), 'customer_name' => 'Pelanggan edge QA', 'customer_phone' => '081234567890', 'warranty_days' => 30, 'items' => [['product_id' => $p->id, 'qty' => 2147483648, 'price' => 4294967295]]]);
Expense::create(['spent_on' => now('Asia/Makassar')->toDateString(), 'category' => 'lainnya', 'description' => str_repeat('Deskripsi pengeluaran panjang sintetis QA ', 6), 'amount' => 4294967295, 'created_by' => $admin->id]);
file_put_contents($root.'/edge-paths.json', json_encode(['tracking' => $paths, 'sale' => '/penjualan/'.$s->id]));
chmod($root.'/edge-paths.json', 0600);
echo json_encode(['mode' => 'private_sqlite_only', 'tracking_states' => array_keys($paths), 'valid_integer_total' => InvoiceLines::calculate([['type' => 'part', 'qty' => 2147483648, 'price' => 4294967295, 'cost' => 4294967295]])['price'], 'sale_total' => $s->items()->firstOrFail()->subtotalPrice(), 'large_expense' => 4294967295])."\n";
