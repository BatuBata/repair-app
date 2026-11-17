<?php

namespace Tests\Feature\Platform;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PlatformOwnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'repair.platform_owner.username' => 'owner.saas',
            'repair.platform_owner.password_hash' => password_hash('platform-secret', PASSWORD_BCRYPT),
        ]);
    }

    public function test_platform_login_fails_closed_and_limits_wrong_credentials(): void
    {
        config(['repair.platform_owner.password_hash' => null]);
        $this->get('/platform/login')->assertStatus(503);
        config(['repair.platform_owner.password_hash' => password_hash('platform-secret', PASSWORD_BCRYPT)]);

        foreach (range(1, 5) as $attempt) {
            $this->post('/platform/login', ['username' => 'owner.saas', 'password' => 'wrong-'.$attempt])
                ->assertSessionHasErrors('username');
        }
        $this->post('/platform/login', ['username' => 'owner.saas', 'password' => 'wrong-6'])->assertStatus(429);
    }

    public function test_platform_session_is_separate_and_invalidated_when_config_changes(): void
    {
        $this->post('/platform/login', ['username' => 'OWNER.SAAS', 'password' => 'platform-secret'])
            ->assertRedirect('/platform');
        $this->get('/platform')->assertOk()->assertSee('Kelola toko');

        config(['repair.platform_owner.password_hash' => password_hash('replacement-secret', PASSWORD_BCRYPT)]);
        $this->get('/platform')->assertForbidden();
    }

    public function test_platform_logout_invalidates_session(): void
    {
        $this->withSession(['platform_owner' => $this->fingerprint()])->post('/platform/logout')
            ->assertRedirect('/platform/login');
        $this->get('/platform')->assertForbidden();
    }

    public function test_tenant_user_cannot_use_platform_login_or_dashboard(): void
    {
        $tenant = Tenant::create(['name' => 'Tenant biasa']);
        app(TenantContext::class)->set($tenant);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        app(TenantContext::class)->forget();

        $this->actingAs($user)->get('/platform/login')->assertForbidden();
        $this->withSession(['platform_owner' => $this->fingerprint()])->get('/platform')->assertForbidden();
    }

    public function test_dashboard_lists_only_tenant_identity_and_owner(): void
    {
        $tenant = Tenant::create(['name' => 'Sumber HP']);
        app(TenantContext::class)->set($tenant);
        User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Owner Sumber', 'username' => 'owner.sumber', 'role' => 'admin']);
        Product::create(['name' => 'Rahasia stok tenant', 'cost_price' => 10, 'sell_price' => 20, 'stock' => 3]);
        app(TenantContext::class)->forget();

        $this->withSession(['platform_owner' => $this->fingerprint()])->get('/platform')
            ->assertOk()->assertSee('Sumber HP')->assertSee('Owner Sumber')->assertSee('owner.sumber')
            ->assertDontSee('Rahasia stok tenant');
    }

    public function test_platform_creates_tenant_and_owner_atomically(): void
    {
        $this->withSession(['platform_owner' => $this->fingerprint()])->post('/platform/tenants', [
            'store_name' => ' Toko Baru ',
            'name' => ' Owner Baru ',
            'username' => 'OWNER.BARU',
            'password' => '123123123',
            'password_confirmation' => '123123123',
        ])->assertRedirect('/platform')->assertSessionHas('status');

        $tenant = Tenant::where('name', 'Toko Baru')->firstOrFail();
        $owner = User::withoutGlobalScope('tenant')->where('username', 'owner.baru')->firstOrFail();
        $this->assertSame($tenant->id, $owner->tenant_id);
        $this->assertTrue(Hash::check('123123123', $owner->password));
        $this->assertDatabaseHas('store_settings', ['tenant_id' => $tenant->id, 'name' => '']);
        $tenantCount = Tenant::count();

        $this->withSession(['platform_owner' => $this->fingerprint()])->post('/platform/tenants', [
            'store_name' => 'Duplikat', 'name' => 'Owner', 'username' => 'owner.baru',
            'password' => '123123123', 'password_confirmation' => '123123123',
        ])->assertSessionHasErrors('username');
        $this->assertSame($tenantCount, Tenant::count());
    }

    public function test_platform_can_toggle_tenant_without_deleting_data(): void
    {
        $tenant = Tenant::create(['name' => 'Toko Aktif']);
        $this->withSession(['platform_owner' => $this->fingerprint()])
            ->patch("/platform/tenants/{$tenant->id}/status")
            ->assertRedirect('/platform');
        $this->assertFalse($tenant->fresh()->is_active);
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id]);
    }

    public function test_platform_mutations_keep_web_csrf_middleware(): void
    {
        foreach (['platform.tenants.store', 'platform.tenants.toggle', 'platform.logout'] as $name) {
            $this->assertContains('web', Route::getRoutes()->getByName($name)->gatherMiddleware());
        }
    }

    private function fingerprint(): string
    {
        return hash('sha256', config('repair.platform_owner.username')."\0".config('repair.platform_owner.password_hash'));
    }
}
