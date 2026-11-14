<?php
use App\Livewire\Users\Index;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

class PasswordCallersTest extends Tests\TestCase
{
    use RefreshDatabase;

    public function test_admin_create_and_reset_use_nine_character_boundary(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $component = Livewire::test(Index::class)->call('create')
            ->set('name', 'QA Synthetic')->set('username', 'qa_t032')
            ->set('password', '33333333')->set('password_confirmation', '33333333')
            ->call('save')->assertHasErrors('password');
        $this->assertDatabaseMissing('users', ['username' => 'qa_t032']);
        $component->set('password', '333333333')->set('password_confirmation', '333333333')
            ->call('save')->assertHasNoErrors();
        $user = User::where('username', 'qa_t032')->firstOrFail();
        $this->assertTrue(Hash::check('333333333', $user->password));
        $component->call('resetPassword', $user->id)
            ->set('password', '44444444')->set('password_confirmation', '44444444')
            ->call('save')->assertHasErrors('password');
        $this->assertTrue(Hash::check('333333333', $user->fresh()->password));
        $component->set('password', '444444444')->set('password_confirmation', 'different')
            ->call('save')->assertHasErrors('password');
        $component->set('password_confirmation', '444444444')
            ->call('save')->assertHasNoErrors();
        $this->assertTrue(Hash::check('444444444', $user->fresh()->password));
    }
}
