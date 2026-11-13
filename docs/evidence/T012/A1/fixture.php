<?php

use App\Models\Expense;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require getcwd().'/vendor/autoload.php';
$app = require getcwd().'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (($argv[1] ?? '') === 'init') {
    User::factory()->create(['username' => 't012_admin', 'name' => 'Admin Sintetis T012', 'role' => 'admin', 'password' => getenv('QA_PASSWORD')]);
    User::factory()->create(['username' => 't012_teknisi', 'name' => 'Teknisi Sintetis T012', 'password' => getenv('QA_PASSWORD')]);
    StoreSetting::create(['id' => 1, 'name' => 'Anugerah QA T012', 'address' => 'Alamat sintetis', 'phone' => '081234567890', 'default_warranty_days' => 30]);
    echo "Private synthetic users and store ready; empty products.\n";
} elseif (($argv[1] ?? '') === 'products') {
    foreach ([['Kabel HDMI 2 meter', 25000, 40000, 3, true], ['Remote televisi universal dengan tombol besar untuk semua merek elektronik rumah tangga dan perangkat penerima siaran digital', 35000, 55000, 0, true], ['Adaptor antena lama', 12000, 20000, 5, false]] as [$name, $cost, $sell, $stock, $active]) {
        Product::create(['name' => $name, 'cost_price' => $cost, 'sell_price' => $sell, 'stock' => $stock, 'is_active' => $active]);
    }
    echo "Three private products ready.\n";
} elseif (($argv[1] ?? '') === 'proof') {
    $product = Product::where('name', 'Pengisi daya USB T012')->firstOrFail();
    echo json_encode(['product' => $product->only(['name', 'cost_price', 'sell_price', 'stock']), 'entries' => $product->stockEntries()->get(['qty', 'cost_price', 'entry_date', 'note'])->toArray(), 'expenses' => Expense::count(), 'driver' => config('database.default'), 'private_session' => str_starts_with(config('session.files'), getenv('QA_RUNTIME')), 'private_view' => str_starts_with(config('view.compiled'), getenv('QA_RUNTIME'))], JSON_PRETTY_PRINT)."\n";
}
