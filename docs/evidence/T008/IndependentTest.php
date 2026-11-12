<?php
namespace Tests\Evidence\T008;

use App\Livewire\Users\Index;
use App\Livewire\Settings\{Store, Categories};
use App\Models\{User, Category};
use App\Services\CreateServiceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

// Independent assertions against the pinned candidate; no product/test source changes.
class IndependentTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_admin_routes_enforce_guest_and_technician_boundaries(): void
    {
        $routes = ['/', '/dashboard', '/pengaturan/pengguna', '/pengaturan/toko', '/pengaturan/kategori'];
        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
            $this->getJson($route)->assertUnauthorized();
        }
        $this->actingAs(User::factory()->create(['role'=>'teknisi']));
        foreach ($routes as $route) $this->get($route)->assertForbidden();
    }

    public function test_all_public_admin_actions_reject_stale_inactive_actor_including_upload_protocol(): void
    {
        foreach ([Index::class=>['create','edit','resetPassword','cancel','save','toggleActive','$refresh'], Categories::class=>['create','edit','cancel','save','toggleActive','$refresh'], Store::class=>['save','$refresh','_startUpload','_finishUpload','_removeUpload']] as $class=>$actions) {
            foreach ($actions as $action) {
                $actor=User::factory()->create(['role'=>'admin']);
                $this->actingAs($actor);
                $component=Livewire::test($class);
                User::whereKey($actor->id)->update(['is_active'=>false]);
                $component->call($action,1)->assertForbidden();
            }
        }
    }

    public function test_upload_protocol_rejects_demoted_actor_and_cannot_write_public_storage(): void
    {
        Storage::fake('public');
        foreach (['_startUpload','_finishUpload','_removeUpload'] as $action) {
            $actor=User::factory()->create(['role'=>'admin']); $this->actingAs($actor);
            $component=Livewire::test(Store::class);
            User::whereKey($actor->id)->update(['role'=>'teknisi']);
            $component->call($action,'logo',[],false)->assertForbidden();
        }
        $this->assertSame([],Storage::disk('public')->allFiles());
    }

    public function test_inactive_category_is_rejected_for_new_order_but_old_category_and_creator_survive(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $this->actingAs($admin);
        $creator=User::factory()->create(['name'=>'Teknisi historis T008']);
        $cat=Category::create(['name'=>'Kategori historis T008']);
        $input=['customer_name'=>'Sintetis T008','customer_phone'=>'081234567890','category_id'=>$cat->id,'complaint'=>'Uji QA'];
        $old=app(CreateServiceOrder::class)->create($input,$creator);
        Livewire::test(Categories::class)->call('toggleActive',$cat->id)->assertHasNoErrors();
        Livewire::test(Index::class)->call('toggleActive',$creator->id)->assertHasNoErrors();
        $this->assertSame('Kategori historis T008',$old->fresh()->category->name);
        $this->assertSame('Teknisi historis T008',$old->fresh()->creator->name);
        $this->assertFalse(Category::where('is_active',true)->whereKey($cat->id)->exists());
        try { app(CreateServiceOrder::class)->create($input,$creator); $this->fail('Inactive category accepted'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('category_id',$e->errors()); }
        $this->assertDatabaseCount('service_orders',1);
    }

    public function test_form_save_cannot_remove_last_admin_via_combined_role_status_changes(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $this->actingAs($admin);
        foreach ([['teknisi',false],['teknisi',true],['admin',false]] as [$role,$active]) {
            Livewire::test(Index::class)->call('edit',$admin->id)->set('role',$role)->set('is_active',$active)->call('save')->assertHasErrors('is_active');
            $this->assertTrue($admin->fresh()->is_active); $this->assertSame('admin',$admin->fresh()->role->value);
        }
    }
    public function test_category_mode_is_locked_against_forged_create_or_edit_state(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin']));
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        Livewire::test(Categories::class)->set('mode','edit');
    }

    public function test_upload_error_callback_rechecks_current_status_and_role(): void
    {
        foreach ([['role'=>'teknisi'],['is_active'=>false]] as $change) {
            $actor=User::factory()->create(['role'=>'admin']); $this->actingAs($actor);
            $c=Livewire::test(Store::class); User::whereKey($actor->id)->update($change);
            $c->call('_uploadErrored','logo',null,false)->assertForbidden();
        }
    }
}
