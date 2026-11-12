<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_profile_routes_redirect_to_approved_profile(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['/settings', '/settings/profile', '/settings/security'] as $path) {
            $this->get($path)->assertRedirect('/profil');
        }
        $this->get(route('profile.edit'))->assertOk()->assertSee('Ganti password')->assertDontSee('Delete account')->assertDontSee('Email');
    }

    public function test_account_deletion_endpoints_are_absent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->delete('/user')->assertNotFound();
        $this->delete('/settings/profile')->assertRedirect('/profil');
        $this->assertNotNull($user->fresh());
    }
}
