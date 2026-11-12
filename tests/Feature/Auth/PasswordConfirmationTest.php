<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_confirmation_and_password_update_endpoints_are_absent(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/user/confirm-password')->assertNotFound();
        $this->post('/user/confirm-password')->assertNotFound();
        $this->get('/user/confirmed-password-status')->assertNotFound();
        $this->put('/user/password', [])->assertNotFound();
    }
}
