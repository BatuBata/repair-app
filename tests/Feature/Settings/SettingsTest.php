<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Categories;
use App\Livewire\Settings\Store;
use App\Models\Category;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_saves_blank_initial_store_fields_and_warranty_range(): void
    {
        $this->admin();
        Livewire::test(Store::class)
            ->set('name', 'Toko Anugerah')->set('address', 'Jalan Uji')->set('phone', '081234567890')
            ->set('warranty_terms', 'Simpan nota untuk klaim.')->set('default_warranty_days', '4294967295')
            ->call('save')->assertHasNoErrors()->assertSet('status', 'Pengaturan toko berhasil disimpan.');
        $this->assertDatabaseHas('store_settings', ['id' => 1, 'name' => 'Toko Anugerah', 'default_warranty_days' => 4294967295]);

        Livewire::test(Store::class)->set('name', '')->set('address', '')->set('phone', '')
            ->set('warranty_terms', '')->set('default_warranty_days', '0')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('store_settings', ['id' => 1, 'name' => '', 'address' => '', 'phone' => '', 'default_warranty_days' => 0]);
    }

    public function test_store_field_and_unsigned_range_validation_keeps_saved_values(): void
    {
        $this->admin();
        StoreSetting::create(['id' => 1, 'name' => 'Toko tetap', 'address' => '', 'phone' => '', 'default_warranty_days' => 30]);
        Livewire::test(Store::class)->set('name', str_repeat('x', 256))->set('phone', str_repeat('1', 16))
            ->set('default_warranty_days', '-1')->call('save')
            ->assertHasErrors(['name', 'phone', 'default_warranty_days']);
        $this->assertDatabaseHas('store_settings', ['id' => 1, 'name' => 'Toko tetap', 'default_warranty_days' => 30]);
    }

    public function test_failed_store_write_keeps_existing_logo_and_removes_new_upload(): void
    {
        Storage::fake('public');
        $this->admin();
        Storage::disk('public')->put('logos/existing.png', 'existing');
        StoreSetting::create(['id' => 1, 'name' => 'Toko tetap', 'address' => '', 'phone' => '', 'logo_path' => 'logos/existing.png', 'default_warranty_days' => 30]);
        StoreSetting::saving(fn () => throw new RuntimeException('simulated write failure'));

        try {
            Livewire::test(Store::class)->set('logo', UploadedFile::fake()->image('new-logo.png'))->call('save');
            $this->fail('Expected the synthetic database write failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('simulated write failure', $exception->getMessage());
        }

        $this->assertSame('logos/existing.png', StoreSetting::findOrFail(1)->logo_path);
        Storage::disk('public')->assertExists('logos/existing.png');
        $this->assertSame(['logos/existing.png'], Storage::disk('public')->allFiles('logos'));
    }

    public function test_logo_upload_accepts_raster_and_rejects_svg_spoof_and_oversize(): void
    {
        Storage::fake('public');
        $this->admin();
        Livewire::test(Store::class)->set('logo', UploadedFile::fake()->image('customer-name.png'))
            ->call('save')->assertHasNoErrors();
        $path = StoreSetting::findOrFail(1)->logo_path;
        $this->assertStringStartsWith('logos/', $path);
        $this->assertNotSame('customer-name.png', basename($path));
        Storage::disk('public')->assertExists($path);

        $component = Livewire::test(Store::class)->set('logo', UploadedFile::fake()->createWithContent('bad.png', '<?php echo "not an image";')->mimeType('application/x-php'))
            ->call('save')->assertHasErrors('logo');
        $this->assertSame($path, StoreSetting::findOrFail(1)->logo_path);
        $component->set('logo', UploadedFile::fake()->create('too-large.jpg', 2049, 'image/jpeg'))
            ->call('save')->assertHasErrors('logo');
        $this->assertSame($path, StoreSetting::findOrFail(1)->logo_path);
        $component->set('logo', UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'))
            ->call('save')->assertHasErrors('logo');
        $this->assertSame($path, StoreSetting::findOrFail(1)->logo_path);
    }

    public function test_category_can_be_added_renamed_and_deactivated_without_breaking_old_order(): void
    {
        $this->admin();
        Livewire::test(Categories::class)->call('create')->set('name', 'TV')->call('save')->assertHasNoErrors();
        $category = Category::where('name', 'TV')->firstOrFail();
        $order = app(CreateServiceOrder::class)->create([
            'customer_name' => 'Rani', 'customer_phone' => '081234567890', 'category_id' => $category->id,
            'complaint' => 'Tidak menyala',
        ], User::firstOrFail());

        Livewire::test(Categories::class)->call('edit', $category->id)->set('name', 'Televisi')->call('save')->assertHasNoErrors();
        Livewire::test(Categories::class)->call('toggleActive', $category->id)->assertHasNoErrors();
        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame('Televisi', $order->fresh()->category->name);
        $this->assertDatabaseHas('service_orders', ['id' => $order->id, 'category_id' => $category->id]);
    }

    public function test_categories_validate_unique_name_and_invalid_ids_without_mutation(): void
    {
        $this->admin();
        $category = Category::create(['name' => 'TV']);
        Livewire::test(Categories::class)->call('create')->set('name', 'TV')->call('save')->assertHasErrors('name');
        Livewire::test(Categories::class)->call('edit', $category->id)->set('name', '')->call('save')->assertHasErrors('name');
        foreach (['invalid', -1, []] as $id) {
            Livewire::test(Categories::class)->call('edit', $id)->assertHasErrors('id');
            Livewire::test(Categories::class)->call('toggleActive', $id)->assertHasErrors('id');
        }
        Livewire::test(Categories::class)->call('edit', 999)->assertNotFound();
        $this->assertDatabaseCount('categories', 1);
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_category_edit_id_is_locked(): void
    {
        $this->admin();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Categories::class)->set('categoryId', 999);
    }

    public function test_logo_path_is_locked_from_client_changes(): void
    {
        $this->admin();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Store::class)->set('logo_path', 'logos/forged.php');
    }

    public function test_settings_routes_and_every_component_action_require_an_active_admin(): void
    {
        $this->get('/pengaturan/toko')->assertRedirect('/login');
        $this->get('/pengaturan/kategori')->assertRedirect('/login');

        $technician = User::factory()->create();
        $this->actingAs($technician)->get('/pengaturan/toko')->assertForbidden();
        $this->get('/pengaturan/kategori')->assertForbidden();

        foreach ([['component' => Store::class, 'actions' => ['save', '$refresh']], ['component' => Categories::class, 'actions' => ['create', 'edit', 'cancel', 'save', 'toggleActive', '$refresh']]] as $case) {
            foreach ($case['actions'] as $action) {
                $admin = User::factory()->create(['role' => 'admin']);
                $this->actingAs($admin);
                $component = Livewire::test($case['component']);
                User::whereKey($admin->id)->update(['role' => 'teknisi']);
                $component->call($action, 1)->assertForbidden();
            }
        }
    }

    public function test_stale_inactive_admin_cannot_save_store_or_categories(): void
    {
        $admin = $this->admin();
        $store = Livewire::test(Store::class)->set('name', 'Tidak disimpan');
        $category = Category::create(['name' => 'Tetap aktif']);
        $categories = Livewire::test(Categories::class);
        User::whereKey($admin->id)->update(['is_active' => false]);
        $store->call('save')->assertForbidden();
        $categories->call('toggleActive', $category->id)->assertForbidden();
        $this->assertDatabaseMissing('store_settings', ['name' => 'Tidak disimpan']);
        $this->assertTrue($category->fresh()->is_active);
    }
}
