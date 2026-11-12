<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_not_an_auth_requirement_and_verification_endpoints_are_absent(): void
    {
        $user = User::factory()->create(['email' => null, 'email_verified_at' => null]);
        $this->assertNotInstanceOf(MustVerifyEmail::class, $user);
        $this->actingAs($user)->get('/profil')->assertOk();
        $this->get('/email/verify')->assertNotFound();
        $this->get('/email/verify/1/hash')->assertNotFound();
        $this->post('/email/verification-notification')->assertNotFound();
    }
}
