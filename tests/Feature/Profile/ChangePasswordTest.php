<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile\ChangePassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_roles_change_only_their_own_password(): void
    {
        foreach (['admin', 'teknisi'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $token = $user->remember_token;
            $this->actingAs($user)->get('/profil')->assertOk();
            Livewire::test(ChangePassword::class)->set('current_password', 'password')->set('password', 'changed-password')->set('password_confirmation', 'changed-password')->call('updatePassword')->assertHasNoErrors()->assertSet('password', '')->assertSee('Password berhasil diubah.');
            $this->assertTrue(Hash::check('changed-password', $user->fresh()->password));
            $this->assertNotSame($token, $user->fresh()->remember_token);
        }
    }

    public function test_current_password_strength_and_confirmation_are_checked(): void
    {
        $this->actingAs($user = User::factory()->create());
        Livewire::test(ChangePassword::class)->set('current_password', 'wrong')->set('password', 'valid-password')->set('password_confirmation', 'valid-password')->call('updatePassword')->assertHasErrors('current_password');
        Livewire::test(ChangePassword::class)->set('current_password', 'password')->set('password', 'short')->set('password_confirmation', 'different')->call('updatePassword')->assertHasErrors('password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_deactivated_user_cannot_call_a_previously_mounted_action(): void
    {
        $this->actingAs($user = User::factory()->create());
        $component = Livewire::test(ChangePassword::class)->set('current_password', 'password')->set('password', 'changed-password')->set('password_confirmation', 'changed-password');
        User::whereKey($user->id)->update(['is_active' => false]);
        $component->call('updatePassword')->assertForbidden();
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
