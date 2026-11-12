<?php

namespace Tests\Feature\Users;

use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_creates_edits_reactivates_and_resets_accounts_without_email(): void
    {
        $this->admin();
        $component = Livewire::test(Index::class)->call('create')->set('name', 'Rizky')->set('username', 'RIZKY')->set('password', 'initial-password')->set('password_confirmation', 'initial-password')->call('save')->assertHasNoErrors()->assertSet('mode', 'list');
        $user = User::where('username', 'rizky')->firstOrFail();
        $this->assertNull($user->email);
        $this->assertTrue(Hash::check('initial-password', $user->password));
        $component->call('edit', $user->id)->set('role', 'admin')->call('save')->assertHasNoErrors();
        $this->assertSame('admin', $user->fresh()->role->value);
        $component->call('toggleActive', $user->id)->assertHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);
        $component->call('toggleActive', $user->id)->assertHasNoErrors();
        $this->assertTrue($user->fresh()->is_active);
        $token = $user->fresh()->remember_token;
        $component->call('resetPassword', $user->id)->set('password', 'reset-password')->set('password_confirmation', 'reset-password')->call('save')->assertHasNoErrors()->assertSet('password', '');
        $this->assertTrue(Hash::check('reset-password', $user->fresh()->password));
        $this->assertNotSame($token, $user->fresh()->remember_token);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_create_validation_preserves_values_and_checks_duplicate_username(): void
    {
        $admin = $this->admin();
        $component = Livewire::test(Index::class)->call('create')->set('name', 'Rizky')->set('username', $admin->username)->set('role', 'owner')->set('password', 'short')->set('password_confirmation', 'wrong')->call('save')->assertHasErrors(['username', 'role', 'password'])->assertSet('name', 'Rizky');
        $component->set('username', 'bad user')->call('save')->assertHasErrors('username');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_last_active_admin_cannot_be_deactivated_or_demoted(): void
    {
        $admin = $this->admin();
        User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $component = Livewire::test(Index::class)->call('toggleActive', $admin->id)->assertHasErrors('is_active');
        $component->call('edit', $admin->id)->set('role', 'teknisi')->call('save')->assertHasErrors('is_active');
        $component->set('role', 'admin')->set('is_active', false)->call('save')->assertHasErrors('is_active');
        $this->assertTrue($admin->fresh()->is_active);
        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_two_admins_can_change_one_but_not_then_remove_the_survivor(): void
    {
        $this->admin();
        $other = User::factory()->create(['role' => 'admin']);
        Livewire::test(Index::class)->call('edit', $other->id)->set('role', 'teknisi')->call('save')->assertHasNoErrors();
        $this->assertSame(1, User::where('role', 'admin')->where('is_active', true)->count());
        $admin = User::where('role', 'admin')->firstOrFail();
        Livewire::test(Index::class)->call('toggleActive', $admin->id)->assertHasErrors('is_active');
    }

    public function test_invalid_ids_are_rejected_without_mutation(): void
    {
        $this->admin();
        foreach (['edit', 'resetPassword', 'toggleActive'] as $action) {
            foreach (['invalid', -1, []] as $invalid) {
                Livewire::test(Index::class)->call($action, $invalid)->assertHasErrors('id');
            }
            Livewire::test(Index::class)->call($action, 99999)->assertNotFound();
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_technician_and_inactive_admin_cannot_mount_user_management(): void
    {
        foreach ([['role' => 'teknisi'], ['role' => 'admin', 'is_active' => false]] as $attributes) {
            $this->actingAs(User::factory()->create($attributes));
            Livewire::test(Index::class)->assertForbidden();
        }
    }

    public function test_all_actions_recheck_role_after_mount(): void
    {
        foreach (['create', 'edit', 'resetPassword', 'cancel', 'save', 'toggleActive', '$refresh'] as $action) {
            $admin = $this->admin();
            $component = Livewire::test(Index::class);
            User::whereKey($admin->id)->update(['role' => 'teknisi']);
            $component->call($action, $admin->id)->assertForbidden();
            $this->assertTrue($admin->fresh()->is_active);
        }
    }

    public function test_deactivation_after_mount_blocks_direct_action_and_protected_output(): void
    {
        $admin = $this->admin();
        $component = Livewire::test(Index::class)->call('create')->set('name', 'Tidak boleh')->set('username', 'blocked')->set('password', 'new-password')->set('password_confirmation', 'new-password');
        User::whereKey($admin->id)->update(['is_active' => false]);
        $component->call('save')->assertForbidden()->assertDontSee('Akun Toko Anugerah');
        $this->assertDatabaseMissing('users', ['username' => 'blocked']);
    }

    public function test_client_cannot_change_locked_user_id(): void
    {
        $this->admin();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Index::class)->set('userId', 999);
    }

    public function test_client_cannot_change_locked_mode_to_bypass_account_form(): void
    {
        $this->admin();
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Index::class)->set('mode', 'reset');
    }
}
