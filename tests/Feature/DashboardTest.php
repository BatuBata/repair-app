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

    public function test_admin_gets_shell_without_fabricated_metrics(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'email_verified_at' => null]));
        $this->get('/')->assertOk()->assertSee('Beranda')->assertSee('Kelola pengguna')->assertDontSee('Rp1.');
        $this->get('/dashboard')->assertRedirect('/');
    }
}
