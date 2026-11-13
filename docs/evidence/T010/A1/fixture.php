<?php

use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Enums\ServiceItemType;
use App\Livewire\Users\Index;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require getcwd().'/vendor/autoload.php';
$runtime = getenv('T010_RUNTIME');
$app = require getcwd().'/bootstrap/app.php';
$app->useStoragePath($runtime.'/storage');
$app->make(Kernel::class)->bootstrap();
if ($argv[1] === 'config') {
    file_put_contents(getenv('APP_CONFIG_CACHE'), '<?php return '.var_export(config()->all(), true).';');
    chmod(getenv('APP_CONFIG_CACHE'), 0600);
    echo "Private config cache prepared.\n";
} elseif ($argv[1] === 'seed') {
    Artisan::call('migrate', ['--force' => true]);
    foreach (['admin', 'controller', 'tech', 'inactive'] as $kind) {
        User::factory()->create(['name' => match ($kind) {
            'admin' => 'Pak Arif', 'controller' => 'Admin Uji', 'tech' => 'Rizky', default => 'Teknisi Nonaktif'
        }, 'username' => 't010_'.$kind, 'role' => in_array($kind, ['admin', 'controller'], true) ? 'admin' : 'teknisi', 'is_active' => $kind !== 'inactive', 'password' => getenv('QA_PASSWORD')]);
    }
    Category::create(['name' => 'Laptop']);
    StoreSetting::create(['name' => 'Anugerah', 'address' => '', 'phone' => '', 'default_warranty_days' => 30]);
    $creator = User::where('username', 't010_tech')->firstOrFail();
    for ($i = 0; $i < 6; $i++) {
        app(CreateServiceOrder::class)->create(['customer_name' => 'Pak Hendra Wijaya', 'customer_phone' => '081347201186', 'category_id' => Category::firstOrFail()->id, 'complaint' => 'LCD bergaris, kadang mati sendiri', 'item_description' => 'Asus X441, hitam'], $creator);
    }
    echo json_encode(['users' => User::count(), 'orders' => ServiceOrder::count(), 'driver' => config('database.default'), 'private_views' => str_starts_with(config('view.compiled'), $runtime)])."\n";
} elseif ($argv[1] === 'layout') {
    auth()->login(User::where('username', 't010_admin')->firstOrFail());
    $ids = [];
    foreach ([360, 768, 1280] as $width) {
        $order = app(CreateServiceOrder::class)->create(['customer_name' => 'Pak Hendra Wijaya', 'customer_phone' => '081347201186', 'category_id' => Category::firstOrFail()->id, 'complaint' => 'LCD bergaris', 'item_description' => 'Asus X441, hitam'], User::where('username', 't010_tech')->firstOrFail());
        app(ManageServiceOrder::class)->assign($order->id, User::where('username', 't010_tech')->firstOrFail()->id);
        $order->items()->createMany([['type' => 'part', 'description' => 'LCD 14 inci HD', 'qty' => 1, 'price' => 1000000, 'cost' => 700000], ['type' => 'jasa', 'description' => 'Jasa ganti LCD', 'qty' => 1, 'price' => 100000, 'cost' => 0]]);
        $ids[$width] = $order->id;
    }
    file_put_contents($runtime.'/layout-ids.json', json_encode($ids));
    echo "Private layout fixtures prepared.\n";
} elseif ($argv[1] === 'demote' || $argv[1] === 'restore') {
    auth()->login(User::where('username', 't010_controller')->firstOrFail());
    $component = new Index;
    $component->edit(User::where('username', 't010_admin')->firstOrFail()->id);
    $component->role = $argv[1] === 'demote' ? 'teknisi' : 'admin';
    $component->save();
    echo "Synthetic admin role updated through Users::edit/save.\n";
} elseif ($argv[1] === 'inventory') {
    echo json_encode(ServiceOrder::with('items')->get()->map(fn ($order) => ['id' => $order->id, 'status' => $order->status->value, 'invoice_date' => $order->invoiced_on?->toDateString(), 'pickup_date' => $order->picked_up_on?->toDateString(), 'warranty_days' => $order->warranty_days, 'jasa_cost_zero' => $order->items->where('type', ServiceItemType::Jasa)->every(fn ($item) => $item->cost === 0)])->all())."\n";
} else {
    throw new RuntimeException('Unknown fixture mode.');
}
