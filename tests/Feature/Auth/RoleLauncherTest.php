<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleLauncherTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_discovers_the_same_manifest_as_the_app_shell(): void
    {
        $this->get('/login')->assertOk()->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('/mulai', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#f2f5f6', $manifest['theme_color']);
        $this->assertSame('Toko Anugerah', $manifest['name']);
    }

    public function test_launcher_requires_login_then_redirects_to_the_current_role_home(): void
    {
        $this->get('/mulai')->assertRedirect('/login');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/mulai')->assertRedirect('/');
        $this->get(route('app.start'))->assertRedirect('/');
        User::whereKey($admin->id)->update(['role' => 'teknisi']);
        $this->get('/mulai')->assertRedirect('/tts/baru');
        $this->get('/profil')->assertOk()->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false);
        $this->get('/')->assertForbidden();
        $this->get('/dashboard')->assertForbidden();
        $this->get('/tts/baru')->assertNotFound();
        User::whereKey($admin->id)->update(['role' => 'admin']);
        $this->get('/mulai')->assertRedirect('/');
    }

    public function test_inactive_session_cannot_use_the_launcher(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);
        User::whereKey($user->id)->update(['is_active' => false]);
        $this->get('/mulai')->assertForbidden();
        $this->assertGuest();
    }
}
