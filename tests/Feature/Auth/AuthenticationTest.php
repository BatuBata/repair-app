<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_approved_username_form(): void
    {
        $this->get('/login')->assertOk()->assertSee('Username')->assertSee('Ingat saya di HP ini')->assertSee('Minta admin toko')->assertDontSee('name="email"', false)->assertDontSee('Passkey');
    }

    public function test_both_roles_login_without_email_and_ignore_intended_admin_url(): void
    {
        foreach (['admin' => '/', 'teknisi' => '/tts/baru'] as $role => $path) {
            $user = User::factory()->create(['role' => $role, 'email' => null, 'email_verified_at' => null]);
            $this->withSession(['url.intended' => '/pengaturan/pengguna'])->post('/login', ['username' => strtoupper($user->username), 'password' => 'password'])->assertSessionHasNoErrors()->assertRedirect($path);
            $this->assertAuthenticatedAs($user);
            $this->post('/logout')->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_invalid_password_unknown_username_and_inactive_account_are_rejected(): void
    {
        $user = User::factory()->create();
        foreach ([[$user->username, 'wrong'], ['missing', 'password']] as [$username, $password]) {
            $this->post('/login', compact('username', 'password'))->assertSessionHasErrors('username');
            $this->assertGuest();
        }
        $user->update(['is_active' => false]);
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_required_fields_are_validated_and_input_is_preserved(): void
    {
        $this->from('/login')->post('/login', ['username' => 'operator'])->assertRedirect('/login')->assertSessionHasErrors('password')->assertSessionHasInput('username', 'operator');
        $this->post('/login', [])->assertSessionHasErrors(['username', 'password']);
        $this->post('/login', ['username' => ['invalid'], 'password' => ['invalid']])->assertSessionHasErrors(['username', 'password']);
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        $username = 'throttle-user';
        RateLimiter::clear('login'.$username.'|127.0.0.1');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => $username, 'password' => 'wrong'])->assertSessionHasErrors('username');
        }
        $this->post('/login', ['username' => $username, 'password' => 'wrong'])->assertStatus(429)->assertSee('Terlalu banyak percobaan masuk')->assertSee('value="throttle-user"', false);
    }

    public function test_remember_me_issues_framework_recaller_cookie_and_rotates_session(): void
    {
        $user = User::factory()->create();
        $oldId = session()->getId();
        $response = $this->post('/login', ['username' => $user->username, 'password' => 'password', 'remember' => '1']);
        $response->assertRedirect('/tts/baru')->assertCookie(auth()->guard()->getRecallerName());
        $this->assertNotSame($oldId, session()->getId());
    }

    public function test_deactivation_affects_an_existing_session(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get('/')->assertOk();
        User::whereKey($user->id)->update(['is_active' => false]);
        $this->get('/')->assertForbidden()->assertDontSee('Kelola pengguna');
        $this->assertGuest();
    }

    public function test_role_change_affects_an_existing_session(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user)->get('/pengaturan/pengguna')->assertOk();
        User::whereKey($user->id)->update(['role' => 'teknisi']);
        $this->get('/pengaturan/pengguna')->assertForbidden()->assertDontSee('Akun Toko Anugerah');
    }

    public function test_password_reset_invalidates_an_existing_password_session(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->password;
        $user->update(['password' => 'reset-password']);
        $this->actingAs($user)->withSession(['password_hash_web' => $oldHash])->get('/profil')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_reset_immediately_after_login_also_invalidates_the_session(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/tts/baru');
        User::whereKey($user->id)->update(['password' => Hash::make('reset-password')]);
        $this->get('/profil')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_technician_redirect_has_no_fake_downstream_module(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/tts/baru');
        $this->get('/tts/baru')->assertNotFound();
    }
}
