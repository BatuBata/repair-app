<?php

namespace Tests\Feature\Tenancy;

use App\Console\Commands\CreateTenant;
use App\Livewire\Settings\Categories;
use App\Livewire\Settings\Store;
use App\Livewire\Users\Index;
use App\Models\Category;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class IdentityTest extends TestCase
{
    use RefreshDatabase;

    private function tenantUser(string $name, string $username): User
    {
        $tenant = Tenant::create(['name' => $name]);

        return User::factory()->create(['tenant_id' => $tenant->id, 'name' => $name, 'username' => $username, 'role' => 'admin']);
    }

    private function context(User $user): void
    {
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
    }

    public function test_guest_login_has_generic_branding(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Repair App')
            ->assertSee('/icons/repair-app-192.png')
            ->assertSee('width="24" height="24"', false)
            ->assertDontSee('Anugerah');
    }

    public function test_global_login_restores_the_right_tenant_from_a_session(): void
    {
        $first = $this->tenantUser('Toko A', 'owner.a');
        $second = $this->tenantUser('Toko B', 'owner.b');
        foreach ([$first, $second] as $owner) {
            $this->context($owner);
            StoreSetting::create(['name' => 'Identitas '.$owner->username, 'address' => '']);
        }
        foreach ([$first, $second] as $user) {
            app(TenantContext::class)->forget();
            $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/');
            auth()->forgetGuards();
            $this->get('/pengaturan/pengguna')->assertOk()->assertSee($user->name)
                ->assertDontSee($user->is($first) ? $second->username : $first->username)
                ->assertSee('Akun Identitas '.$user->username)->assertDontSee('Anugerah');
            $this->assertAuthenticatedAs($user);
            $this->assertFalse(app(TenantContext::class)->has());
            $this->post('/logout')->assertRedirect('/login');
        }
    }

    public function test_remember_cookie_restores_tenant_and_inactive_tenant_cannot_reenter(): void
    {
        $user = $this->tenantUser('Toko B', 'owner.b');
        $name = auth()->guard()->getRecallerName();
        $response = $this->post('/login', ['username' => $user->username, 'password' => 'password', 'remember' => '1']);
        $cookie = $response->getCookie($name, false);
        $this->assertNotNull($cookie);
        session()->flush();
        auth()->forgetGuards();
        $this->withUnencryptedCookie($name, $cookie->getValue())->get('/pengaturan/pengguna')->assertOk()->assertSee($user->name);
        $this->assertTrue(auth()->guard()->viaRemember());
        Tenant::whereKey($user->tenant_id)->update(['is_active' => false]);
        $this->get('/pengaturan/pengguna')->assertForbidden();
        $this->assertGuest();
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_users_are_scoped_and_global_username_uniqueness_is_kept(): void
    {
        $foreign = $this->tenantUser('Foreign Owner', 'owner.foreign');
        $local = $this->tenantUser('Local Owner', 'owner.local');
        $this->actingAs($local);
        Livewire::test(Index::class)->assertSee('Local Owner')->assertDontSee('Foreign Owner');
        foreach (['edit', 'resetPassword', 'toggleActive'] as $action) {
            Livewire::test(Index::class)->call($action, $foreign->id)->assertNotFound();
        }
        $component = Livewire::test(Index::class)->call('create')->set('name', 'Teknisi lokal')
            ->set('username', $foreign->username)->set('password', '123456789')->set('password_confirmation', '123456789')
            ->call('save')->assertHasErrors('username');
        $component->set('username', 'tech.local')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('users', ['username' => 'tech.local', 'tenant_id' => $local->tenant_id]);
        Livewire::test(Index::class)->call('toggleActive', $local->id)->assertHasErrors('is_active');
        $this->assertTrue($foreign->fresh()->is_active);
    }

    public function test_models_ignore_forged_tenant_id_and_fail_closed_without_context(): void
    {
        $foreign = $this->tenantUser('Foreign', 'foreign');
        $local = $this->tenantUser('Local', 'local');
        $category = Category::create(['name' => 'Android', 'tenant_id' => $foreign->tenant_id]);
        $store = StoreSetting::create(['address' => '', 'tenant_id' => $foreign->tenant_id]);
        $user = User::create(['name' => 'Teknisi', 'username' => 'tech', 'password' => '123456789', 'tenant_id' => $foreign->tenant_id]);
        foreach ([$category, $store, $user] as $model) {
            $this->assertSame($local->tenant_id, $model->tenant_id);
        }
        app(TenantContext::class)->forget();
        $this->assertSame(0, Category::count());
        $this->assertSame(0, StoreSetting::count());
        $this->assertSame(0, User::count());
    }

    public function test_categories_allow_same_name_across_tenants_and_reject_foreign_ids(): void
    {
        $foreign = $this->tenantUser('Foreign', 'foreign');
        $category = Category::create(['name' => 'Android']);
        $local = $this->tenantUser('Local', 'local');
        $this->actingAs($local);
        $component = Livewire::test(Categories::class)->assertDontSee('Android')->call('create')
            ->set('name', 'Android')->call('save')->assertHasNoErrors();
        $component->call('create')->set('name', 'Android')->call('save')->assertHasErrors('name');
        foreach (['edit', 'toggleActive'] as $action) {
            Livewire::test(Categories::class)->call($action, $category->id)->assertNotFound();
        }
        $this->context($foreign);
        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_store_settings_and_logo_directory_follow_active_tenant(): void
    {
        Storage::fake('public');
        $foreign = $this->tenantUser('Foreign', 'foreign');
        $foreignStore = StoreSetting::create(['name' => 'Foreign shop', 'address' => '', 'phone' => '08111']);
        $local = $this->tenantUser('Local', 'local');
        $this->actingAs($local);
        Livewire::test(Store::class)->assertSet('name', '')->set('name', 'Local shop')
            ->set('logo', UploadedFile::fake()->image('logo.png'))->call('save')->assertHasNoErrors();
        $store = StoreSetting::firstOrFail();
        $this->assertSame($local->tenant_id, $store->tenant_id);
        $this->assertStringStartsWith('logos/'.$local->tenant_id.'/', $store->logo_path);
        $this->assertNull(StoreSetting::find($foreignStore->id));
        $this->assertDatabaseHas('store_settings', ['id' => $foreignStore->id, 'name' => 'Foreign shop', 'phone' => '08111']);
        Livewire::test(Store::class)->assertSet('name', 'Local shop')->set('name', 'Local updated')->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('store_settings', 2);
    }

    public function test_provisioning_is_atomic_and_password_stays_secret(): void
    {
        app(TenantContext::class)->forget();
        $this->artisan('tenant:create')->expectsQuestion('Nama toko', 'Toko HP')->expectsQuestion('Nama owner', 'Owner HP')
            ->expectsQuestion('Username owner', 'OWNER.HP')->expectsQuestion('Password awal (minimal 9 karakter)', '123456789')
            ->doesntExpectOutputToContain('123456789')->assertSuccessful();
        $tenant = Tenant::where('name', 'Toko HP')->firstOrFail();
        $owner = User::withoutGlobalScope('tenant')->where('username', 'owner.hp')->firstOrFail();
        $this->assertSame($tenant->id, $owner->tenant_id);
        $this->assertSame('admin', $owner->role->value);
        $this->assertTrue($owner->is_active);
        $this->assertTrue(Hash::check('123456789', $owner->password));
        $this->assertDatabaseHas('store_settings', ['tenant_id' => $tenant->id, 'name' => '', 'address' => '', 'default_warranty_days' => 30]);
        $this->assertSame(0, DB::table('categories')->where('tenant_id', $tenant->id)->count());
        $this->assertFalse(app(TenantContext::class)->has());
        $command = app(CreateTenant::class);
        $this->assertSame([], $command->getDefinition()->getArguments());
    }

    public function test_failed_provisioning_rolls_back_and_restores_previous_context(): void
    {
        $local = $this->tenantUser('Existing', 'existing');
        $before = [Tenant::count(), DB::table('users')->count(), DB::table('store_settings')->count()];
        User::creating(fn () => throw new RuntimeException('123456789'));
        $this->artisan('tenant:create')->expectsQuestion('Nama toko', 'Rollback shop')->expectsQuestion('Nama owner', 'Owner')
            ->expectsQuestion('Username owner', 'rollback')->expectsQuestion('Password awal (minimal 9 karakter)', '123456789')
            ->expectsOutput('Toko gagal dibuat. Seluruh perubahan dibatalkan.')->doesntExpectOutputToContain('123456789')->assertFailed();
        $this->assertSame($before, [Tenant::count(), DB::table('users')->count(), DB::table('store_settings')->count()]);
        $this->assertSame($local->tenant_id, app(TenantContext::class)->id());
    }

    public function test_provisioning_rejects_duplicate_username_and_short_password_without_writes(): void
    {
        $this->tenantUser('Existing', 'existing');
        $before = Tenant::count();
        $this->artisan('tenant:create')->expectsQuestion('Nama toko', 'Blocked shop')->expectsQuestion('Nama owner', 'Owner')
            ->expectsQuestion('Username owner', 'existing')->expectsQuestion('Password awal (minimal 9 karakter)', '12345678')
            ->doesntExpectOutputToContain('12345678')->assertFailed();
        $this->assertSame($before, Tenant::count());
        $this->artisan('tenant:create', ['--no-interaction' => true])->expectsOutput('Jalankan tenant:create secara interaktif.')->assertFailed();
    }
}
