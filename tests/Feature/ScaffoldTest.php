<?php

namespace Tests\Feature;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ScaffoldTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->get('/login')->assertOk()->assertDontSee('/register"', false);
        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Contoh',
            'email' => 'contoh@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_project_defaults_and_isolated_test_database(): void
    {
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('Asia/Makassar', config('app.timezone'));
        $this->assertSame('Asia/Makassar', date_default_timezone_get());
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    public function test_routes_from_multiple_modules_are_loaded(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_dompdf_can_render_an_a5_document(): void
    {
        $pdf = Pdf::loadHTML('<h1>Contoh Anugerah</h1>')->setPaper('a5');

        $this->assertStringStartsWith('%PDF-', $pdf->output());
    }
}
