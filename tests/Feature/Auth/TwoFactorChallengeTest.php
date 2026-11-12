<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_and_passkey_endpoints_are_absent(): void
    {
        $this->get('/two-factor-challenge')->assertNotFound();
        $this->post('/two-factor-challenge')->assertNotFound();
        $this->actingAs(User::factory()->create());
        foreach (['/user/two-factor-authentication', '/user/confirmed-two-factor-authentication', '/user/two-factor-recovery-codes', '/passkeys', '/passkeys/login', '/passkeys/confirm'] as $path) {
            $this->post($path)->assertNotFound();
        }
        foreach (['/user/two-factor-qr-code', '/user/two-factor-secret-key', '/user/two-factor-recovery-codes', '/passkeys/login/options', '/passkeys/register/options', '/.well-known/passkey-endpoints'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}
