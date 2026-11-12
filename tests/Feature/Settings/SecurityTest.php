<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Exceptions\ComponentNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_appearance_security_and_account_deletion_components_expose_no_actions(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/settings/appearance')->assertNotFound();
        foreach (['profile', 'security', 'delete-user-form', 'delete-user-modal', 'two-factor-setup-modal', 'two-factor.recovery-codes'] as $component) {
            try {
                Livewire::test('pages::settings.'.$component)->assertNotFound();
            } catch (ComponentNotFoundException) {
                $this->assertTrue(true);
            }
        }
    }
}
