<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_and_dashboard_require_login_and_admin(): void
    {
        foreach (['/', '/dashboard', '/pengaturan/pengguna'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        foreach (['/', '/dashboard', '/pengaturan/pengguna'] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_admin_gets_real_dashboard_and_empty_states(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'email_verified_at' => null]));
        $this->get('/')->assertOk()->assertSee('Beranda')->assertSee('Omzet hari ini')->assertSee('Rp0')->assertSee('Tidak ada garansi yang akan habis')->assertDontSee('Kelola pengguna');
        $this->get('/dashboard')->assertRedirect('/');
    }
}
