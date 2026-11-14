<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
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

    public function test_form_fields_show_placeholders_when_empty(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.field name="name" label="Nama" />
            <x-ui.field name="phone" label="Telepon" type="tel" />
            <x-ui.field name="password" label="Password baru" type="password" />
            <x-ui.field name="quantity" label="Jumlah" type="number" />
            <x-ui.field name="date" label="Tanggal" type="date" />
        BLADE);

        $this->assertStringContainsString('placeholder="Masukkan nama"', $html);
        $this->assertStringContainsString('placeholder="08xxxxxxxxxx"', $html);
        $this->assertStringContainsString('placeholder="Minimal 9 karakter"', $html);
        $this->assertMatchesRegularExpression('/name="quantity"[^>]*placeholder="0"/', $html);
        $this->assertStringNotContainsString('type="date" placeholder=', $html);

        $this->get('/login')
            ->assertOk()
            ->assertSee('placeholder="Masukkan username"', false)
            ->assertSee('placeholder="Masukkan password"', false);

        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        Livewire::actingAs($admin)->test(UsersIndex::class)
            ->call('create')
            ->assertSeeHtml('placeholder="mis. Budi Santoso"')
            ->assertSeeHtml('placeholder="mis. budi"')
            ->assertSeeHtml('placeholder="Minimal 9 karakter"')
            ->assertSeeHtml('placeholder="Ulangi password baru"');
    }

    public function test_static_view_icons_exist_in_the_shared_sprite(): void
    {
        $usedIcons = [];

        foreach (File::allFiles(resource_path('views')) as $view) {
            preg_match_all('/<x-ui\.icon\b[^>]*\bname="([a-z0-9-]+)"/', $view->getContents(), $matches);
            $usedIcons = [...$usedIcons, ...$matches[1]];
        }

        $sprite = File::get(public_path('icons/lucide.svg'));
        $missingIcons = array_values(array_filter(
            array_unique($usedIcons),
            fn (string $icon): bool => ! str_contains($sprite, 'id="i-'.$icon.'"'),
        ));

        $this->assertNotEmpty($usedIcons);
        $this->assertSame([], $missingIcons, 'Ikon tidak tersedia di sprite Lucide: '.implode(', ', $missingIcons));
    }
}
