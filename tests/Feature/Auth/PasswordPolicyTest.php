<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    public function test_nine_characters_are_required_without_composition_rules_in_every_environment(): void
    {
        $original = app()->environment();
        try {
            foreach (['production', 'testing'] as $environment) {
                app()->instance('env', $environment);
                foreach (['222222222', 'abcdefghi', 'ABCDEFGHI', '!!!!!!!!!'] as $password) {
                    $this->assertTrue(Validator::make(['password' => $password], [
                        'password' => ['required', 'string', Password::default()],
                    ])->passes(), $environment);
                }
                foreach (['', '22222222', 'abcdefgh'] as $password) {
                    $this->assertTrue(Validator::make(['password' => $password], [
                        'password' => ['required', 'string', Password::default()],
                    ])->fails(), $environment);
                }
            }
        } finally {
            app()->instance('env', $original);
        }
    }
}
